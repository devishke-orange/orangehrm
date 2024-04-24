<?php

namespace OrangeHRM\DevTools\Command;

use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\Config;
use OrangeHRM\Entity\OAuthAccessToken;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InsertPermanentBearerToken extends Command
{
    use EntityManagerHelperTrait;

    protected static $defaultName = 'insert-token';
    private SymfonyStyle $io;
    private const PERMANENT_TOKEN = '0e5f3284f2df8fdcc81f601080710e1013afe010ad674e7f4898d6a1ffdd519bd42f1165e501079b';
    private const ENCRYPTION_KEY = 'aJmvC3dsidQB6xhfJN7GzAY+Gj/Ofl27RPardtqK+gs=';
    private const TOKEN_ENCRYPTION_KEY = 'AAs8cg3JauC6nUqfF8kDnLZ6Uun2q5dHQ9zkLtS7MAM=';

    /**
     * @inheritDoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }


    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setDescription('Insert permanent bearer token');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->info("Inserting token...");

        $accessToken = $this->getRepository(OAuthAccessToken::class)->findOneBy([
            'accessToken' => self::PERMANENT_TOKEN
        ]);

        if (null !== $accessToken) {
            $this->io->error("Access token already exists.");
            return Command::FAILURE;
        }

        $accessToken = new OAuthAccessToken();
        $accessToken->setAccessToken(self::PERMANENT_TOKEN);
        $accessToken->setClientId(1);
        $accessToken->setUserId(1);
        $accessToken->setExpiryDateTime(new \DateTimeImmutable("2099-12-31 11:59:59"));
        $accessToken->setRevoked(0);

        $this->persist($accessToken);

        $encryptionKeyConfig = $this->getRepository(Config::class)->findOneBy([
            'name' => 'oauth.encryption_key'
        ]);
        $tokenEncryptionKeyConfig = $this->getRepository(Config::class)->findOneBy([
            'name' => 'oauth.token_encryption_key'
        ]);

        if (null === $tokenEncryptionKeyConfig || null === $encryptionKeyConfig) {
            $this->io->error("Encryption key not found in the config table.");
            return Command::FAILURE;
        }

        $encryptionKeyConfig->setValue(self::ENCRYPTION_KEY);
        $tokenEncryptionKeyConfig->setValue(self::TOKEN_ENCRYPTION_KEY);

        $this->persist($encryptionKeyConfig);
        $this->persist($tokenEncryptionKeyConfig);

        $this->io->info("Token: def502001f64fdfdc63165f8f62269dba1e133d0727e6ef8c84b9ce32ca18ee0a9f05f7f63f3704af82ed481dcf76956ea382fabf8b66a5668e94d190071d6d1d2e1eed28ac86645eb6d4b0900efcf6e5da9e9dfd87527a2b2da77019089b3be8a2cc8a8f9ff073f8a64933cc54967ce440462c01142766f071771d19fcd37015cb6de198df5be6ed000fd6ef1408f32641769d8746d3539cd52c8b53c3ea442fafafce0");
        $this->io->success("Inserted permanent bearer token.");
        return Command::SUCCESS;
    }
}
