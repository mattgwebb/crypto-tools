<?php

namespace App\Controller;

use App\Entity\BotAlgorithm;
use App\Entity\CurrencyPair;
use App\Entity\TimeFrames;
use App\Model\BotAlgorithmManager;
use App\Service\BinanceAPI;
use App\Service\Strategies;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{

    /**
     * @var Strategies
     */
    private $strategies;

    /** @var BotAlgorithmManager */
    private $manager;

    /**
     * TestController constructor.
     * @param Strategies $strategies
     * @param BotAlgorithmManager $manager
     */
    public function __construct(Strategies $strategies, BotAlgorithmManager $manager)
    {
        $this->strategies = $strategies;
        $this->manager = $manager;
    }

    /**
     * @Route("/dashboard", name="dashboard", methods={"GET"})
     */
    public function dashboard()
    {
        return $this->render('dashboard.html.twig');
    }

    /**
     * @Route("/algo-form", name="new_algo_form", methods={"GET"})
     */
    public function newAlgo()
    {
        $currencyPairs = $this->getDoctrine()
            ->getRepository(CurrencyPair::class)
            ->findAll();

        $timeFrames = [
            TimeFrames::TIMEFRAME_5M => "5M",
            TimeFrames::TIMEFRAME_15M => "15M",
            TimeFrames::TIMEFRAME_30M => "30M",
            TimeFrames::TIMEFRAME_45M => "45M",
            TimeFrames::TIMEFRAME_1H => "1H",
            TimeFrames::TIMEFRAME_2H => "2H",
            TimeFrames::TIMEFRAME_3H => "3H",
            TimeFrames::TIMEFRAME_4H => "4H",
            TimeFrames::TIMEFRAME_1D => "1D",
            TimeFrames::TIMEFRAME_1W => "1W"
        ];

        return $this->render('algoform.html.twig', [
            "currencies" => $currencyPairs,
            "time_frames" => $timeFrames,
            "strategies" => $this->strategies->getStrategiesList()
        ]);
    }

    /**
     * @Route("/algos", name="list_algos", methods={"GET"})
     * @param Request $request
     * @return Response
     */
    public function listAlgos()
    {
        $algos = $this->getDoctrine()
            ->getRepository(BotAlgorithm::class)
            ->findAll();

        return $this->render('list_algos.html.twig', [
            "algos" => $algos
        ]);
    }

    /**
     * @Route("/algos", name="new_algo", methods={"POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function processNewAlgo(Request $request)
    {
        $currencyPairID = $request->request->get('currency');

        $currencyPair = $this->getDoctrine()
            ->getRepository(CurrencyPair::class)
            ->find($currencyPairID);

        if($currencyPair) {
            $algo = new BotAlgorithm();
            $algo->setCurrencyPair($currencyPair);
            $algo->setTimeFrame($request->request->get('time_frame'));
            $algo->setStrategy($request->request->get('strategy'));
            $algo->setStopLoss($request->request->get('stop_loss'));
            $algo->setTakeProfit($request->request->get('take_profit'));
            $algo->setObservations($request->request->get('observations'));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($algo);
            $entityManager->flush();
        }

        return $this->redirectToRoute('list_algos');
    }


    /**
     * @Route("/algos/{id}/result", name="run_algo_test", methods={"GET"})
     * @param int $id
     * @return Response
     * @throws \Exception
     */
    public function runAlgoTest(int $id)
    {
        /** @var BotAlgorithm $algo */
        $algo = $this->getDoctrine()
            ->getRepository(BotAlgorithm::class)
            ->find($id);

        if(!$algo) {
            return $this->render('error.html.twig', [
                "error" => "Algo not found.",
            ]);
        }
        $api = new BinanceAPI();
        //$test =  $api->getUserBalance();

        //$trades = $this->manager->runTest($algo);

        $mockTrades = [["trade"=>"long","time"=>"Wed Jul 17 13:59:59","price"=> 9157.02],
                        ["trade"=>"short","time"=>"Fri Aug 2 14:59:59","price"=>10554.78,"percentage"=>0.15264354560763227,"stopLoss_takeProfit" =>false],
                        ["trade"=>"long","time"=>"Wed Aug 14 18:59:59","price"=>10347.13],
                        ["trade"=>"short","time"=>"Tue Sep 3 9:59:59","price"=>10368.72,"percentage"=>0.002086568932641253,"stopLoss_takeProfit"=>false],
                        ["trade"=>"long","time"=>"Wed Sep 25 13:59:59","price"=>8326.64],
                        ["trade"=>"short","time"=>"Thu Oct 10 6:59:59","price"=>8574.98,"percentage"=>0.029824755243411438,"stopLoss_takeProfit"=>false],
                        ];

        return $this->render('test.html.twig', [
            "symbol" => $algo->getCurrencyPair()->getSymbol(),
            "trades" => $mockTrades,
            "result" => ["percentage" => 0.18455486978368, "investment" => 1189.9999]
        ]);
    }
}