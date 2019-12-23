<?php

namespace App\Controller;

use App\Entity\BotAlgorithm;
use App\Entity\CurrencyPair;
use App\Entity\TimeFrames;
use App\Model\BotAlgorithmManager;
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

    /**
     * @var BotAlgorithmManager
     */
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
     * @Route("/chart/{id}", name="get_chart", methods={"GET"})
     * @param int $id
     * @return Response
     */
    public function getChart(int $id)
    {
        $currencyPairRepo = $this->getDoctrine()
            ->getRepository(CurrencyPair::class);

        /** @var CurrencyPair $currencyPair */
        $currencyPair = $currencyPairRepo
            ->find($id);

        $candles = $currencyPairRepo->getCandlesByTimeFrame($currencyPair, TimeFrames::TIMEFRAME_1H);
        return new Response(json_encode($candles));
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
     * @Route("/edit-algo-form/{id}", name="edit_algo_form", methods={"GET"})
     * @param int $id
     * @return Response
     */
    public function editAlgo(int $id)
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

        return $this->render('edit_algoform.html.twig', [
            "algo" => $algo,
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
     * @Route("/algos/result", name="run_algo_test", methods={"POST"})
     * @return Response
     * @throws \Exception
     */
    public function runAlgoTest(Request $request)
    {
        $id = $request->request->get('algo-id');
        $startTime = $request->request->get('start-time');
        $endTime = $request->request->get('end-time');

        /** @var BotAlgorithm $algo */
        $algo = $this->getDoctrine()
            ->getRepository(BotAlgorithm::class)
            ->find($id);

        if(!$algo) {
            return $this->render('error.html.twig', [
                "error" => "Algo not found.",
            ]);
        }

        $trades = $this->manager->runTest($algo, $startTime, $endTime);

        return $this->render('algo_test_result.html.twig', [
            "pair" => $algo->getCurrencyPair(),
            "trades" => $trades,
            "divergences" => []
        ]);
    }

    /**
     * @Route("/algos/divergence/result", name="run_algo_divergence_test", methods={"POST"})
     * @return Response
     * @throws \Exception
     */
    public function runAlgoDivergenceTest(Request $request)
    {
        $id = $request->request->get('algo-id');
        $startTime = $request->request->get('start-time');
        $endTime = $request->request->get('end-time');

        /** @var BotAlgorithm $algo */
        $algo = $this->getDoctrine()
            ->getRepository(BotAlgorithm::class)
            ->find($id);

        if(!$algo) {
            return $this->render('error.html.twig', [
                "error" => "Algo not found.",
            ]);
        }

        //$trades = $this->manager->runTest($algo, $startTime, $endTime);
        $trades = [];
        $divergences = $this->manager->runDivergenceTest($algo, $startTime, $endTime);

        /*$mockTrades = [["trade"=>"long","time"=>"Thu Sep 6 13=>59=>59","timestamp"=>1536235199000,"price"=>6408.65],
            ["trade"=>"short","time"=>"Sat Sep 22 2=>59=>59","timestamp"=>1537577999000,"price"=>6802.95,"percentage"=>6.15,"stopLoss_takeProfit"=>false],
            ["trade"=>"long","time"=>"Tue Sep 25 20=>59=>59","timestamp"=>1537901999000,"price"=>6347.58],
            ["trade"=>"short","time"=>"Tue Dec 18 5=>59=>59","timestamp"=>1545109199000,"price"=>3530.34,"percentage"=>-44.38,"stopLoss_takeProfit"=>false],
            ["trade"=>"long","time"=>"Fri Jan 11 2=>59=>59","timestamp"=>1547171999000,"price"=>3592.95],
            ["trade"=>"short","time"=>"Sat Feb 9 5=>59=>59","timestamp"=>1549688399000,"price"=>3652.72,"percentage"=>1.66,"stopLoss_takeProfit"=>false],
            ["trade"=>"long","time"=>"Wed Jul 17 13=>59=>59","timestamp"=>1563364799000,"price"=>9157.02],
            ["trade"=>"short","time"=>"Fri Aug 2 14=>59=>59","timestamp"=>1564750799000,"price"=>10554.78,"percentage"=>15.26,"stopLoss_takeProfit"=>false],
            ["trade"=>"long","time"=>"Wed Aug 14 18=>59=>59","timestamp"=>1565801999000,"price"=>10347.13],
            ["trade"=>"short","time"=>"Tue Sep 3 9=>59=>59","timestamp"=>1567497599000,"price"=>10368.72,"percentage"=>0.21,"stopLoss_takeProfit"=>false],
            ["trade"=>"long","time"=>"Wed Sep 25 13=>59=>59","timestamp"=>1569412799000,"price"=>8326.64],
            ["trade"=>"short","time"=>"Thu Oct 10 6=>59=>59","timestamp"=>1570683599000,"price"=>8574.98,"percentage"=>2.98,"stopLoss_takeProfit"=>false],
            ["trade"=>"long","time"=>"Thu Oct 24 3=>59=>59","timestamp"=>1571882399000,"price"=>7440.25]];*/

        return $this->render('algo_test_result.html.twig', [
            "pair" => $algo->getCurrencyPair(),
            "trades" => $trades,
            "divergences" => $divergences
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
            $algo->setName($request->request->get('name'));
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
     * @Route("/algos/{id}", name="edit_algo", methods={"POST"})
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function processEditAlgo(Request $request, int $id)
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
        $algo->setName($request->request->get('name'));
        $algo->setTimeFrame($request->request->get('time_frame'));
        $algo->setStrategy($request->request->get('strategy'));
        $algo->setStopLoss($request->request->get('stop_loss'));
        $algo->setTakeProfit($request->request->get('take_profit'));
        $algo->setObservations($request->request->get('observations'));

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($algo);
        $entityManager->flush();

        return $this->redirectToRoute('list_algos');
    }
}