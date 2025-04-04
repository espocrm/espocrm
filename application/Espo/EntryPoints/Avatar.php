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

    private string $lightTextColor = '#FFF';
    private string $darkTextColor = '#6e6e6e';
    private int $darkThreshold = 200;

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

    private int $maxAge = 600;
    private int $staleWhileRevalidate = 864000;

    private function getColor(User $user): string
    {
        if ($user->getUserName() === SystemUser::NAME) {
            return $this->metadata->get(['app', 'avatars', 'systemColor']) ?? $this->systemColor;
        }

        if ($user->isPortal()) {
            return $this->metadata->get(['app', 'avatars', 'portalColor']) ?? $this->portalColor;
        }

        if ($user->getAvatarColor()) {
            return $user->getAvatarColor();
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
            $this->renderBlank($request, $response);

            return;
        }

        if ($user->getAvatarId()) {
            if ($this->processEtagMatch($request, $response, $user->getAvatarId())) {
                return;
            }

            $this->show(
                response: $response,
                id: $user->getAvatarId(),
                size: $size,
                disableAccessCheck: true,
                noCacheHeaders: true,
            );

            $response->setHeader('Cache-Control', $this->composeCacheControlHeader());
            $response->setHeader('Etag', $user->getAvatarId());

            return;
        }

        $sizes = $this->getSizes()[$size];

        if (empty($sizes)) {
            $this->renderBlank($request, $response);

            return;
        }

        $textColors = $this->getTextColors();

        $width = $sizes[0];
        $color = $this->getColor($user);
        $textColor = $this->isDark($color) ? $textColors[0] : $textColors[1];

        $name = $this->getName($user, $userId);

        $etag = md5($color . $name);

        if ($this->processEtagMatch($request, $response, $etag)) {
            return;
        }

        $avatar = (new InitialAvatar())->name($name);

        if ($user->getName() && !self::isAllowedLanguage($avatar)) {
            $avatar = $avatar->name($user->getUserName() ?? $userId);
        }

        $image = $avatar
            ->width($width)
            ->height($width)
            ->color($textColor)
            ->fontSize(0.56)
            ->preferBold()
            ->font($this->fontFile)
            ->background($color)
            ->generate();

        $response
            ->setHeader('Cache-Control', $this->composeCacheControlHeader())
            ->setHeader('Content-Type', 'image/png')
            ->setHeader('Etag', $etag);

        $response->writeBody($image->stream('png', 100));
    }

    private function composeCacheControlHeader(): string
    {
        return "max-age=$this->maxAge, stale-while-revalidate=$this->staleWhileRevalidate";
    }

    /**
     * @throws Error
     */
    private function renderBlank(Request $request, Response $response): void
    {
        $etag = 'blank';

        if ($this->processEtagMatch($request, $response, $etag)) {
            return;
        }

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
            ->setHeader('Cache-Control', $this->composeCacheControlHeader())
            ->setHeader('Etag', $etag)
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

    private function isDark(string $color): bool
    {
        $hex = substr($color, 1);

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $value = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return $value < $this->darkThreshold;
    }

    /**
     * @return array{string, string}
     */
    private function getTextColors(): array
    {
        $light = $this->metadata->get("app.avatars.lightTextColor") ?? $this->lightTextColor;
        $dark = $this->metadata->get("app.avatars.darkTextColor") ?? $this->darkTextColor;

        return [$light, $dark];
    }

    private function getName(User $user, string $userId): string
    {
        $name = $user->getName() ?? $user->getUserName() ?? $userId;

        if ($user->getUserName() === SystemUser::NAME) {
            $name = SystemUser::NAME;
        }

        return $name;
    }

    private function processEtagMatch(Request $request, Response $response, string $etag): bool
    {
        $matchEtag = $request->getHeader('If-None-Match');

        if (!$matchEtag) {
            return false;
        }

        if ($matchEtag === $etag) {
            $response->setStatus(304);

            return true;
        }

        return false;
    }
}
