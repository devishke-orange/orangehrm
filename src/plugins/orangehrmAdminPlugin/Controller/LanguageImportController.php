<?php

namespace OrangeHRM\Admin\Controller;

use MessageFormatter;
use OrangeHRM\Admin\Api\LanguageValidationAPI;
use OrangeHRM\Admin\Traits\Service\LocalizationServiceTrait;
use OrangeHRM\Core\Controller\AbstractVueController;
use OrangeHRM\Core\Controller\Common\NoRecordsFoundController;
use OrangeHRM\Core\Controller\Exception\RequestForwardableException;
use OrangeHRM\Core\Vue\Component;
use OrangeHRM\Core\Vue\Prop;
use OrangeHRM\Entity\I18NLanguage;
use OrangeHRM\Framework\Http\Request;
use Symfony\Component\Translation\Translator;

class LanguageImportController extends AbstractVueController
{
    use LocalizationServiceTrait;

    public function preRender(Request $request): void
    {
        $fmt = new MessageFormatter( "en_US", "{0, number} monkeys on {1, number} trees" );
        var_dump($fmt->getPattern());
        var_dump($fmt->parse("123 monkeys on 123 trees 123"));
        var_dump($fmt->format([123, 123, 123]));
        var_dump($fmt->parse($fmt->format([123, 123])));
//        echo "Default pattern: '" . $fmt->getPattern() . "'\n";
//        echo "Formatting result: " . $fmt->format(array(123, 456)) . "\n";

        $fmt = new MessageFormatter("en_US", "{empMatchCount,plural, =0{Matches no employee} one{Matches (1) employee} other {matches (#) employees}}");
        var_dump($fmt->getPattern());
        var_dump($fmt->format(['empMatchCount' => 0]));

        $fmt = new MessageFormatter('en_US', "{action, select, APPROVE {{count, plural, =0 {You are about to Approve no Leave Requests} =1 {You are about to Approve 1 Leave Request} other {You are about to Approve # Leave Requests} }} REJECT {{count, plural, =0 {You are about to Reject no Leave Requests} =1 {You are about to Reject 1 Leave Request} other {You are about to Reject # Leave Requests} }} other {{count, plural, =0 {You are about to Cancel no Leave Requests} =1 {You are about to Cancel 1 Leave Request} other {You are about to Cancel # Leave Requests} }} }");
        var_dump($fmt->getPattern());
        var_dump($fmt->format(['action' => 'APPROVE', 'count' => 5]));

        $fmt = new MessageFormatter('en_US', "Test");
        var_dump($fmt->format(['action' => 'APPROVE', 'count' => 5]));



        $translator = new Translator('en');
        die;


















        if ($request->attributes->has('languageId')) {
            $languageId = $request->attributes->getInt('languageId');
            $language = $this->getLocalizationService()->getLocalizationDao()
                ->getLanguageById($languageId);
            if (
                !$language instanceof I18NLanguage ||
                !$language->isAdded() ||
                !$language->isEnabled()
            ) {
                throw new RequestForwardableException(NoRecordsFoundController::class . '::handle');
            }

            $component = new Component('language-import');
            $component->addProp(new Prop('language-id', Prop::TYPE_NUMBER, $languageId));
            $component->addProp(new Prop('language-name', Prop::TYPE_STRING, $language->getName()));
            $component->addProp(new Prop('allowed-file-types', Prop::TYPE_ARRAY, LanguageValidationAPI::PARAM_RULE_IMPORT_FILE_FORMAT));
            $this->setComponent($component);
        }
    }

}
