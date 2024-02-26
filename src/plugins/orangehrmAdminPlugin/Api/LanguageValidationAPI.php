<?php

namespace OrangeHRM\Admin\Api;

use OrangeHRM\Core\Api\V2\CollectionEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use Symfony\Component\Translation\Util\XliffUtils;

class LanguageValidationAPI extends Endpoint implements CollectionEndpoint
{
    /**
     INSERT INTO ohrm_data_group (`name`, `description`, `can_read`, `can_create`, `can_update`, `can_delete`) VALUES ('apiv2_admin_language_package_import', 'API-v2 Admin - Language Package Import', 0, 1, 0, 0);
     SET @admin_module_id := (SELECT `id` FROM ohrm_module WHERE name = 'admin' LIMIT 1);
     SET @apiv2_admin_language_package_import_data_group_id := (SELECT `id` FROM ohrm_data_group WHERE name = 'apiv2_admin_language_package_import' LIMIT 1);
     INSERT INTO ohrm_api_permission (`api_name`, `module_id`, `data_group_id`) VALUES ('OrangeHRM\\Admin\\Api\\LanguageValidationAPI', @admin_module_id, @apiv2_admin_language_package_import_data_group_id);
     SET @apiv2_admin_language_package_import_data_group_id := (SELECT `id` FROM ohrm_data_group WHERE name = 'apiv2_admin_language_package_import' LIMIT 1);
     SET @admin_role_id := (SELECT `id` FROM ohrm_user_role WHERE `name` = ''Admin'' LIMIT 1);
     INSERT INTO ohrm_user_role_data_group (`can_read`, `can_create`, `can_update`, `can_delete`, `self`, `data_group_id`, `user_role_id`) VALUES (0, 1, 0, 0, 0, @apiv2_admin_language_package_import_data_group_id, @admin_role_id);
     */


    public const PARAMETER_LANGUAGE_ID = 'languageId';
    public const PARAMETER_ATTACHMENT = 'attachment';

    public const PARAM_RULE_IMPORT_FILE_FORMAT = ['text/xml', 'application/xml', 'application/xliff+xml'];
    public const PARAM_RULE_IMPORT_FILE_EXTENSIONS = ['xml', 'xlf'];

    public function getAll(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    public function create(): EndpointResult
    {
        $attachment = $this->getRequestParams()->getAttachment(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_ATTACHMENT
        );

        // Validate XLIFF using Symfony Library
        $xliffValidation = $this->validate($attachment->getContent());

//        var_dump($xliffValidation); die;


        // Parsing XML files using simplexml
//        $xml = simplexml_load_string($attachment->getContent());
//
//        $langStrings = [];
//        foreach ($xml->file->group as $group) {
//            $groupName = $group->attributes()->id->__toString();
//            foreach ($group->children() as $unit) {
//                $unitId = $unit->attributes()->id->__toString();
//                $langStrings[$groupName . '.' . $unitId] = [
//                    'source' => $unit->segment->source->__toString(),
//                    'target' => $unit->segment->target->__toString()
//                ];
//            }
//        }

        return new EndpointResourceResult(
            ArrayModel::class,
            [
                'xliff' => $xliffValidation,
//                'langStrings' => array_keys($langStrings),
//                'langStringsWithTargetAndSource' => $langStrings,
//                'count' => count($langStrings)
            ]
        );
    }

    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_LANGUAGE_ID,
                new Rule(
                    Rules::POSITIVE
                )
            ),
            new ParamRule(
                self::PARAMETER_ATTACHMENT,
                new Rule(
                    Rules::BASE_64_ATTACHMENT,
                    [self::PARAM_RULE_IMPORT_FILE_FORMAT, self::PARAM_RULE_IMPORT_FILE_EXTENSIONS]
                )
            )
        );
    }

    // This function is taken from src/vendor/symfony/translation/Command/XliffLintCommand.php
    private function validate(string $content, ?string $file = null): array
    {
        $errors = [];

        // Avoid: Warning DOMDocument::loadXML(): Empty string supplied as input
        if ('' === trim($content)) {
            return ['file' => $file, 'valid' => true];
        }

        $internal = libxml_use_internal_errors(true);

        $document = new \DOMDocument();
        $document->loadXML($content);

        if (null !== $targetLanguage = $this->getTargetLanguageFromFile($document)) {
            $normalizedLocalePattern = sprintf('(%s|%s)', preg_quote($targetLanguage, '/'), preg_quote(str_replace('-', '_', $targetLanguage), '/'));
            // strict file names require translation files to be named '____.locale.xlf'
            // otherwise, both '____.locale.xlf' and 'locale.____.xlf' are allowed
            // also, the regexp matching must be case-insensitive, as defined for 'target-language' values
            // http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html#target-language
            $expectedFilenamePattern = sprintf('/^.*\.(?i:%s)\.(?:xlf|xliff)/', $normalizedLocalePattern);

            if (0 === preg_match($expectedFilenamePattern, basename($file))) {
                $errors[] = [
                    'line' => -1,
                    'column' => -1,
                    'message' => sprintf('There is a mismatch between the language included in the file name ("%s") and the "%s" value used in the "target-language" attribute of the file.', basename($file), $targetLanguage),
                ];
            }
        }

        foreach (XliffUtils::validateSchema($document) as $xmlError) {
            $errors[] = [
                'line' => $xmlError['line'],
                'column' => $xmlError['column'],
                'message' => $xmlError['message'],
            ];
        }

        libxml_clear_errors();
        libxml_use_internal_errors($internal);

        return ['file' => $file, 'valid' => 0 === \count($errors), 'messages' => $errors];
    }

    private function getTargetLanguageFromFile(\DOMDocument $xliffContents): ?string
    {
        foreach ($xliffContents->getElementsByTagName('file')[0]->attributes ?? [] as $attribute) {
            if ('target-language' === $attribute->nodeName) {
                return $attribute->nodeValue;
            }
        }

        return null;
    }

    public function delete(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }
}
