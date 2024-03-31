<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\EntryPoints;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\NotFoundSilent;
use Espo\Core\Utils\SystemUser;
use Espo\Entities\User;

use LasseRafn\InitialAvatarGenerator\InitialAvatar;
use LasseRafn\StringScript;

/**
 * @noinspection PhpUnused
 */
class Avatar extends Image
{
    private string $systemColor = '#a4b5bd';
    private string $portalColor = '#c9a3d1';

    /**
     * @noinspection SpellCheckingInspection
     * @var string[]
     */
    private array $colorList = [
        '#6fa8d6', // blue
        '#e3bf59', // yellow
        '#d4729b', // red
        '#8093BD', // gray blue
        '#7cbac4', // blue in green
        '#8a7cc2', // purple
        '#77c9b9', // green
        '#d6aa6b', // dark yellow
        '#e6859d', // red
    ];

    /**
     * @noinspection SpellCheckingInspection
     * The explicintly specified font prevents warnings in some environments.
     */
    private string $fontFile = 'vendor/lasserafn/php-initial-avatar-generator/src/fonts/OpenSans-Semibold.ttf';

    private function getColor(User $user): string
    {
        if ($user->getUserName() === SystemUser::NAME) {
            return $this->metadata->get(['app', 'avatars', 'systemColor']) ?? $this->systemColor;
        }

        if ($user->isPortal()) {
            return $this->metadata->get(['app', 'avatars', 'portalColor']) ?? $this->portalColor;
        }

        $hash = $user->getId();

        $length = strlen($hash);

        $sum = 0;

        for ($i = 0; $i < $length; $i++) {
            $sum += ord($hash[$i]);
        }

        $x = $sum % 128 + 1;

        $colorList = $this->metadata->get(['app', 'avatars', 'colorList']) ?? $this->colorList;

        if ($x === 128) {
            $x--;
        }

        $index = intval($x * count($colorList) / 128);

        return $colorList[$index];
    }

    /**
     * @throws BadRequest
     * @throws Error
     * @throws NotFoundSilent
     * @throws ForbiddenSilent
     * @throws NotFound
     */
    public function run(Request $request, Response $response): void
    {
        $userId = $request->getQueryParam('id');
        $size = $request->getQueryParam('size') ?? 'small';

        if (!$userId) {
            throw new BadRequest();
        }

        $user = $this->entityManager->getRDBRepositoryByClass(User::class)->getById( $userId);

        if (!$user) {
            $this->renderBlank($response);

            return;
        }

        if ($user->getAvatarId()) {
            $this->show($response, $user->getAvatarId(), $size, true);

            return;
        }

        $sizes = $this->getSizes()[$size];

        if (empty($sizes)) {
            $this->renderBlank($response);

            return;
        }

        $width = $sizes[0];
        $color = $this->getColor($user);

        $avatar = (new InitialAvatar())
            ->name($user->getName() ?? $user->getUserName() ?? $userId);

        if ($user->getName() && !self::isAllowedLanguage($avatar)) {
            $avatar = $avatar->name($user->getUserName() ?? $userId);
        }

        $image = $avatar
            ->width($width)
            ->height($width)
            ->color('#FFF')
            ->fontSize(0.56)
            ->preferBold()
            ->font($this->fontFile)
            ->background($color)
            ->generate();

        $response
            ->setHeader('Cache-Control', 'max-age=360000, must-revalidate')
            ->setHeader('Content-Type', 'image/png');

        $response->writeBody($image->stream('png', 100));
    }

    /**
     * @throws Error
     */
    private function renderBlank(Response $response): void
    {
        ob_start();

        $img  = imagecreatetruecolor(14, 14);

        if ($img === false) {
            throw new Error();
        }

        imagesavealpha($img, true);

        $color = imagecolorallocatealpha($img, 127, 127, 127, 127);

        if ($color === false) {
            throw new Error();
        }

        imagefill($img, 0, 0, $color);
        imagepng($img);
        imagecolordeallocate($img, $color);

        $contents = ob_get_contents();

        if ($contents === false) {
            throw new Error();
        }

        ob_end_clean();

        imagedestroy($img);

        $response
            ->setHeader('Content-Type', 'image/png')
            ->writeBody($contents);
    }

    private static function isAllowedLanguage(InitialAvatar $avatar): bool
    {
        $initials = $avatar->getInitials();

        if (StringScript::isArabic($initials)) {
            return false;
        }

        if (StringScript::isArmenian($initials)) {
            return false;
        }

        if (StringScript::isBengali($initials)) {
            return false;
        }

        if (StringScript::isGeorgian($initials)) {
            return false;
        }

        if (StringScript::isHebrew($initials)) {
            return false;
        }

        if (StringScript::isMongolian($initials)) {
            return false;
        }

        if (StringScript::isThai($initials)) {
            return false;
        }

        if (StringScript::isTibetan($initials)) {
            return false;
        }

        if (StringScript::isJapanese($initials) || StringScript::isChinese($initials)) {
            return false;
        }

        return true;
    }
}
