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

namespace Espo\Tools\LeadCapture;

use Espo\Core\Exceptions\NotFound;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Address\CountryDataProvider;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DataCache;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Theme\MetadataProvider as ThemeMetadataProvider;
use Espo\Core\Utils\ThemeManager;
use Espo\Entities\Integration;
use Espo\Entities\LeadCapture;
use Espo\Modules\Crm\Entities\Lead;
use Espo\ORM\Defs\EntityDefs;
use Espo\ORM\EntityManager;
use Espo\Tools\App\LanguageService;
use RuntimeException;

class FormService
{
    private const CACHE_KEY_PREFIX = 'leadCaptureForm';

    public function __construct(
        private EntityManager $entityManager,
        private Config $config,
        private Metadata $metadata,
        private Language $defaultLanguage,
        private CountryDataProvider $countryDataProvider,
        private LanguageService $languageService,
        private Language\LanguageFactory $languageFactory,
        private DataCache $dataCache,
        private ThemeManager $themeManager,
        private Config\SystemConfig $systemConfig,
        private ThemeMetadataProvider $themeMetadataProvider,
    ) {}

    /**
     * @return array{LeadCapture, array<string, mixed>, ?string}
     * @throws NotFound
     */
    public function getData(string $id): array
    {
        $leadCapture = $this->getLeadCapture($id);
        $captchaKey = $this->getCaptchaKey($leadCapture);
        $captchaScript = $this->getCaptchaScript($captchaKey);

        $data = $this->getDataInternal($leadCapture);

        $data['captchaKey'] = $captchaKey;

        return [$leadCapture, $data, $captchaScript];
    }

    /**
     * @return array<string, mixed>
     */
    private function getDataInternal(LeadCapture $leadCapture): array
    {
        $cacheKey = $this->getCacheKey($leadCapture);

        if ($this->systemConfig->useCache() && $this->dataCache->has($cacheKey)) {
            return $this->getFromCache($cacheKey);
        }

        $data = $this->prepareData($leadCapture);

        $this->dataCache->store($cacheKey, $data);

        return $data;
    }

    private function getRequestUrl(LeadCapture $leadCapture): string
    {
        $formId = $leadCapture->getFormId();

        if (!$formId) {
            throw new RuntimeException("No API key.");
        }

        return "LeadCapture/form/$formId";
    }

    /**
     * @return string[]
     */
    private function getFieldList(LeadCapture $leadCapture): array
    {
        /** @var string[] $allowedTypeList */
        $allowedTypeList = $this->metadata->get("entityDefs.LeadCapture.fields.fieldList.webFormFieldTypeList") ?? [];

        $entityDefs = $this->entityManager->getDefs()->getEntity(Lead::ENTITY_TYPE);

        $fieldList = [];

        foreach ($leadCapture->getFieldList() as $field) {
            if (!$entityDefs->hasField($field)) {
                continue;
            }

            $itemDefs = $entityDefs->getField($field);

            if (!in_array($itemDefs->getType(), $allowedTypeList)) {
                continue;
            }

            $fieldList[] = $field;
        }

        return $fieldList;
    }

    /**
     * @param string[] $fieldList
     * @param array<string, array<string, mixed>> $languageData
     * @return array<string, array<string, mixed>>
     */
    private function getFieldDefs(
        array $fieldList,
        LeadCapture $leadCapture,
        array &$languageData,
        Language $language
    ): array {

        $entityDefs = $this->entityManager->getDefs()->getEntity(Lead::ENTITY_TYPE);

        $fieldDefs = [];

        foreach ($fieldList as $field) {
            $fieldDefs[$field] = $this->metadata->get("entityDefs.Lead.fields.$field");

            if (!$fieldDefs[$field]) {
                continue;
            }

            $this->applyFieldDefsItem(
                $leadCapture,
                $entityDefs,
                $field,
                $fieldDefs,
                $languageData,
                $language
            );
        }

        return $fieldDefs;
    }

    /**
     * @param string[] $fieldList
     * @return array<int, mixed>
     */
    private function getDetailLayout(array $fieldList): array
    {
        $rows = [];

        foreach ($fieldList as $field) {
            $rows[] = [['name' => $field]];
        }

        return [['rows' => $rows]];
    }

    /**
     * @param string[] $fieldList
     * @return array<string, mixed>
     */
    private function getMetadataFields(array $fieldList): array
    {
        $metadataFields = [];

        $entityDefs = $this->entityManager->getDefs()->getEntity(Lead::ENTITY_TYPE);

        foreach ($fieldList as $field) {
            $type = $entityDefs->getField($field)->getType();

            if (array_key_exists($type, $metadataFields)) {
                continue;
            }

            $metadataFields[$type] = $this->metadata->get("fields.$type");
        }

        return $metadataFields;
    }

    /**
     * @return array<string, mixed>
     */
    private function getConfig(): array
    {
        $params = [
            'decimalMark',
            'thousandSeparator',
            'phoneNumberInternational',
            'phoneNumberExtensions',
            'phoneNumberPreferredCountryList',
            'defaultCurrency',
            'currencyList',
            'currencyDecimalPlaces',
            'addressFormat',
            'dateFormat',
            'timeFormat',
            'timeZone',
            'weekStart',
        ];

        $data = [];

        foreach ($params as $param) {
            $data[$param] = $this->config->get($param);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function getAppParams(): array
    {
        return [
            'addressCountryData' => $this->countryDataProvider->get(),
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $fieldDefs
     * @param array<string, array<string, mixed>> $languageData
     */
    private function applyFieldDefsItem(
        LeadCapture $leadCapture,
        EntityDefs $entityDefs,
        string $field,
        array &$fieldDefs,
        array &$languageData,
        Language $language,
    ): void {

        $fieldDefs[$field]['required'] = $leadCapture->isFieldRequired($field);

        $itDefs = $entityDefs->getField($field);

        $type = $itDefs->getType();

        if ($type === FieldType::ADDRESS) {
            $subList = [
                $field . 'Street',
                $field . 'Country',
                $field . 'State',
                $field . 'PostalCode',
                $field . 'City',
            ];

            foreach ($subList as $sub) {
                /** @var array<string, mixed> $subItem */
                $subItem = $this->metadata->get("entityDefs.Lead.fields.$sub");

                $fieldDefs[$sub] = $subItem;
            }
        }

        if ($type === FieldType::PERSON_NAME) {
            $subList = [
                'first' . ucfirst($field),
                'middle' . ucfirst($field),
                'last' . ucfirst($field),
                'salutation' . ucfirst($field),
            ];

            foreach ($subList as $sub) {
                /** @var array<string, mixed> $subItem */
                $subItem = $this->metadata->get("entityDefs.Lead.fields.$sub");

                $fieldDefs[$sub] = $subItem;
            }
        }

        if ($type === FieldType::EMAIL) {
            if ($leadCapture->optInConfirmation()) {
                $fieldDefs[$field]['required'] = true;
            }

            $fieldDefs[$field]['onlyPrimary'] = true;
        }

        if ($type === FieldType::PHONE) {
            $fieldDefs[$field]['onlyPrimary'] = true;
        }

        if (
            in_array($type, [
                FieldType::ENUM,
                FieldType::MULTI_ENUM,
                FieldType::ARRAY,
                FieldType::CHECKLIST,
            ])
        ) {
            $reference = $itDefs->getParam('optionsReference');

            if ($reference) {
                [$refEntityType, $refField] = explode('.', $reference);

                $options = $this->entityManager
                    ->getDefs()
                    ->tryGetEntity($refEntityType)
                    ?->tryGetField($refField)
                    ?->getParam('options');

                $fieldDefs[$field]['options'] = $options;
                unset($fieldDefs[$field]['optionsReference']);

                $languageData[Lead::ENTITY_TYPE] ??= [];
                $languageData[Lead::ENTITY_TYPE]['options'] ??= [];
                $languageData[Lead::ENTITY_TYPE]['options'][$field] =
                    $language->get("$refEntityType.options.$refField");
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getLanguageData(Language $language): array
    {
        $data = $this->languageService->getDataForFrontendFromLanguage($language);

        $data[Lead::ENTITY_TYPE] = $language->get(Lead::ENTITY_TYPE);

        return $data;
    }

    /**
     * @throws NotFound
     */
    public function getLeadCapture(string $id): LeadCapture
    {
        $leadCapture = $this->entityManager
            ->getRDBRepositoryByClass(LeadCapture::class)
            ->where(['formId' => $id])
            ->findOne();

        if (!$leadCapture || !$leadCapture->hasFormEnabled() || !$leadCapture->isActive()) {
            throw new NotFound();
        }

        return $leadCapture;
    }

    private function getSuccessText(LeadCapture $leadCapture): string
    {
        return $leadCapture->getFormSuccessText() ?? $this->defaultLanguage->translateLabel('Posted');
    }

    private function getCacheKey(LeadCapture $leadCapture): string
    {
        return self::CACHE_KEY_PREFIX . '/' .  $leadCapture->getId();
    }

    /**
     * @return array<string, mixed>
     */
    private function getFromCache(string $cacheKey): array
    {
        /** @var array<string, mixed> */
        return $this->dataCache->get($cacheKey);
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareData(LeadCapture $leadCapture): array
    {
        $language = $this->getLanguage($leadCapture);

        $languageData = $this->getLanguageData($language);
        $fieldList = $this->getFieldList($leadCapture);
        $fieldDefs = $this->getFieldDefs($fieldList, $leadCapture, $languageData, $language);
        $detailLayout = $this->getDetailLayout($fieldList);
        $metadataFields = $this->getMetadataFields($fieldList);
        $successText = $this->getSuccessText($leadCapture);
        $text = $leadCapture->getFormText();
        $config = $this->getConfig();
        $appParams = $this->getAppParams();

        return [
            'requestUrl' => $this->getRequestUrl($leadCapture),
            'fieldDefs' => (object) $fieldDefs,
            'metadata' => [
                'fields' => (object) $metadataFields,
                'app' => [
                    'regExpPatterns' => $this->metadata->get("app.regExpPatterns"),
                ],
            ],
            'isDark' => $this->isDark($leadCapture),
            'detailLayout' => $detailLayout,
            'language' => $languageData,
            'successText' => $successText,
            'text' => $text,
            'title' => $leadCapture->getFormTitle(),
            'config' => (object) $config,
            'appParams' => (object) $appParams,
        ];
    }

    private function getLanguage(LeadCapture $leadCapture): Language
    {
        $language = $this->defaultLanguage;

        if ($leadCapture->getFormLanguage()) {
            $language = $this->languageFactory->create($leadCapture->getFormLanguage());
        }

        return $language;
    }

    private function getCaptchaKey(LeadCapture $leadCapture): ?string
    {
        if (!$leadCapture->hasFormCaptcha()) {
            return null;
        }

        $entity = $this->entityManager
            ->getRepositoryByClass(Integration::class)
            ->getById('GoogleReCaptcha');

        if (!$entity) {
            return null;
        }

        $siteKey = $entity->get('siteKey');

        if (!$siteKey) {
            return null;
        }

        return $siteKey;
    }

    private function getCaptchaScript(?string $siteKey): ?string
    {
        if (!$siteKey) {
            return null;
        }

        return 'https://www.google.com/recaptcha/api.js?render=' . $siteKey;
    }

    private function isDark(LeadCapture $leadCapture): bool
    {
        if (!$leadCapture->getFormTheme()) {
            return $this->themeManager->isDark();
        }

        return $this->themeMetadataProvider->isDark($leadCapture->getFormTheme());
    }
}
