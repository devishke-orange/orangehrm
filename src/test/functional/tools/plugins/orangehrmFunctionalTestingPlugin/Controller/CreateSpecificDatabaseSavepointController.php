<?php

namespace OrangeHRM\FunctionalTesting\Controller;

use OrangeHRM\Core\Controller\PublicControllerInterface;
use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Http\Response;

class CreateSpecificDatabaseSavepointController extends AbstractController implements PublicControllerInterface
{
    public function handle(Request $request): Response
    {
        $savepointName = $request->request->get('savepointName');
        $tableNames = $request->request->get('tableNames', []);
        $this->getDatabaseBackupService()->createSpecificSavepoint($savepointName, $tableNames);
        $response = $this->getResponse();
        $response->setContent(json_encode(['count' => count($tableNames)]));
        return $response;
    }
}
