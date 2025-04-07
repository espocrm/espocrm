<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Tools\DynamicLogic;

use Espo\Core\Field\Date;
use Espo\Core\Field\DateTime;
use Espo\Core\Utils\DateTime\SystemClock;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\Tools\DynamicLogic\ConditionChecker\Options;
use Espo\Tools\DynamicLogic\Exceptions\BadCondition;

use Psr\Clock\ClockInterface;

use Exception;
use RuntimeException;
use DateTimeImmutable;
use DateTimeZone;

/**
 * @since 9.1.0
 */
class ConditionChecker
{
    /**
     * Use `ConditionCheckerFactory` instead.
     */
    public function __construct(
        private Entity $entity,
        private ?User $user = null,
        private Options $options = new Options(),
        private ClockInterface $clock = new SystemClock(),
    ) {}

    /**
     * @throws BadCondition
     */
    public function check(Item $item): bool
    {
        $type = $item->type;
        $value = $item->value;

        if ($type === Type::And) {
            if (!is_array($value)) {
                throw new BadCondition();
            }

            foreach ($value as $subItem) {
                if (!$subItem instanceof Item) {
                    throw new BadCondition();
                }

                if (!$this->check($subItem)) {
                    return false;
                }
            }

            return true;
        }

        if ($type === Type::Or) {
            if (!is_array($value)) {
                throw new BadCondition();
            }

            foreach ($value as $subItem) {
                if (!$subItem instanceof Item) {
                    throw new BadCondition();
                }

                if ($this->check($subItem)) {
                    return true;
                }
            }

            return false;
        }

        if ($type === Type::Not) {
            if (!$value instanceof Item) {
                throw new BadCondition();
            }

            return !$this->check($value);
        }

        if (!$item->attribute) {
            throw new BadCondition("No attribute.");
        }

        $setValue = $this->getAttributeValue($item->attribute);

        if ($type === Type::Equals) {
            return $setValue === $value;
        }

        if ($type === Type::NotEquals) {
            return $setValue !== $value;
        }

        if ($type === Type::IsEmpty) {
            return $setValue === [] ||
                $setValue === null ||
                $setValue === false ||
                $setValue === '';
        }

        if ($type === Type::IsNotEmpty) {
            return !(
                $setValue === [] ||
                $setValue === null ||
                $setValue === false ||
                $setValue === ''
            );
        }

        if ($type === Type::IsTrue) {
            return (bool) $setValue;
        }

        if ($type === Type::IsFalse) {
            return !$setValue;
        }

        if ($type === Type::Contains) {
            if (is_string($setValue) && is_string($value)) {
                return str_contains($setValue, $value);
            }

            if (is_array($setValue)) {
                return in_array($value, $setValue);
            }

            return false;
        }

        if ($type === Type::NotContains) {
            if (is_string($setValue) && is_string($value)) {
                return !str_contains($setValue, $value);
            }

            if (is_array($setValue)) {
                return !in_array($value, $setValue);
            }

            return true;
        }

        if ($type === Type::Has) {
            if (is_array($setValue)) {
                return in_array($value, $setValue);
            }

            return false;
        }

        if ($type === Type::NotHas) {
            if (is_array($setValue)) {
                return !in_array($value, $setValue);
            }

            return true;
        }

        if ($type === Type::StartsWith) {
            if (is_string($setValue) && is_string($value)) {
                return str_starts_with($setValue, $value);
            }

            return false;
        }

        if ($type === Type::EndsWith) {
            if (is_string($setValue) && is_string($value)) {
                return str_ends_with($setValue, $value);
            }

            return false;
        }

        if ($type === Type::Matches) {
            if (is_string($setValue) && is_string($value)) {
                return (bool) preg_match($value, $setValue);
            }

            return false;
        }

        if ($type === Type::GreaterThan) {
            return $setValue > $value;
        }

        if ($type === Type::LessThan) {
            return $setValue < $value;
        }

        if ($type === Type::GreaterThanOrEquals) {
            return $setValue >= $value;
        }

        if ($type === Type::LessThanOrEquals) {
            return $setValue <= $value;
        }

        if ($type === Type::In) {
            if (is_array($value)) {
                return in_array($setValue, $value);
            }

            return false;
        }

        if ($type === Type::NotIn) {
            if (is_array($value)) {
                return !in_array($setValue, $value);
            }

            return true;
        }

        if (!$setValue || !is_string($setValue)) {
            return false;
        }

        if ($type === Type::IsToday) {
            if (strlen($setValue) > 10) {
                $setDateTime = DateTime::fromDateTime($this->createDateTime($setValue));

                $todayStart = $this->createNow()
                    ->withTimezone($this->getTimeZone())
                    ->withTime(0, 0);

                $todayEnd = $todayStart->addDays(1);

                return $todayStart->isLessThanOrEqualTo($setDateTime) && $setDateTime->isLessThan($todayEnd);
            }

            $setDate = Date::fromDateTime($this->createDateTime($setValue));
            $today = $this->createToday();

            return $setDate->isEqualTo($today);
        }

        if ($type === Type::InFuture) {
            if (strlen($setValue) > 10) {
                $setDateTime = DateTime::fromDateTime($this->createDateTime($setValue));

                return $setDateTime->isGreaterThan($this->createNow());
            }

            $setDate = Date::fromDateTime($this->createDateTime($setValue));
            $today = $this->createToday();

            return $setDate->isGreaterThan($today);
        }

        if ($type === Type::InPast) {
            if (strlen($setValue) > 10) {
                $setDateTime = DateTime::fromDateTime($this->createDateTime($setValue));

                return $setDateTime->isLessThan($this->createNow());
            }

            $setDate = Date::fromDateTime($this->createDateTime($setValue));
            $today = $this->createToday();

            return $setDate->isLessThan($today);
        }

        /** @phpstan-ignore-next-line deadCode.unreachable */
        throw new BadCondition("Unimplemented type '$type->value'.");
    }

    private function getAttributeValue(string $attribute): mixed
    {
        if (str_starts_with($attribute, '$')) {
            if (!$this->user) {
                return null;
            }

            if ($attribute === '$user.id') {
                return $this->user->getId();
            }

            if ($attribute === '$user.teamsIds') {
                return $this->user->getTeamIdList();
            }
        }

        return $this->entity->get($attribute);
    }

    private function getTimeZone(): DateTimeZone
    {
        return $this->options->timezone;
    }

    private function createDateTime(string $value): DateTimeImmutable
    {
        try {
            $setDateTime = new DateTimeImmutable($value, $this->getTimeZone());
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        return $setDateTime;
    }

    private function createNow(): DateTime
    {
        return DateTime::fromDateTime($this->clock->now());
    }

    private function createToday(): Date
    {
        $dateTime = $this->clock
            ->now()
            ->setTimezone($this->getTimeZone());

        return Date::fromDateTime($dateTime);
    }
}
