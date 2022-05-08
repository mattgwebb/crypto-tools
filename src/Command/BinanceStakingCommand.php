<?php


namespace App\Command;



use App\Entity\Algorithm\BotAccount;
use App\Exceptions\API\APIException;
use App\Service\Algorithm\BotAccountService;
use App\Service\ThirdPartyAPIs\TelegramBot;
use App\Service\Trade\TradeService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class BinanceStakingCommand extends Command
{

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:staking:subscribe';


    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TradeService
     */
    private $tradeService;

    /**
     * @var TelegramBot
     */
    private $telegramBot;

    /**
     * @var BotAccountService
     */
    private $botAccountService;

    /**
     * BinanceStakingCommand constructor.
     * @param LoggerInterface $botsLogger
     * @param TradeService $tradeService
     * @param TelegramBot $telegramBot
     * @param BotAccountService $botAccountService
     */
    public function __construct(LoggerInterface $botsLogger, TradeService $tradeService,
                                TelegramBot $telegramBot, BotAccountService $botAccountService)
    {
        $this->logger = $botsLogger;
        $this->tradeService = $tradeService;
        $this->telegramBot = $telegramBot;
        $this->botAccountService = $botAccountService;

        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('bot_account_id', InputArgument::REQUIRED, 'Bot id');
        $this->addArgument('product_id', InputArgument::REQUIRED, 'Staking product id');
        $this->addArgument('amount', InputArgument::REQUIRED, 'Amount');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $botAccount = $this->botAccountService->getBotAccount($input->getArgument('bot_account_id'));
        $productId = $input->getArgument('product_id');
        $amount = (float)$input->getArgument('amount');

        try {
            $this->tradeService->subscribeToBinanceStaking($botAccount, $productId, $amount);
            $this->telegramBot->sendNewStakingSubscribeMessage($botAccount, $productId, $amount);

        } catch (APIException $exception) {
            $this->log($botAccount, "ERROR:".$exception->getMessage());
            $this->telegramBot->sendNewErrorMessage($_ENV['TELEGRAM_USER_ID'], 'STAKING SUBSCRIBE', $exception);
        }
    }

    /**
     * @param BotAccount $botAccount
     * @param string $message
     * @param array $context
     */
    private function log(BotAccount $botAccount, string $message, $context = [])
    {
        try {
            $now = new \DateTime();
            $nowString = $now->format('d-m-Y H:i:s');
            $this->logger->info("$nowString: (bot {$botAccount->getId()} staking subscribe) -> $message", $context);
        } catch (\Exception $ex) {}
    }
}
