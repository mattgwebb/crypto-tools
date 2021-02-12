<?php

namespace App\Controller;

use App\Entity\Algorithm\BotAlgorithm;
use App\Entity\Algorithm\TestTypes;
use App\Entity\Data\CurrencyPair;
use App\Entity\Data\ExternalIndicatorDataType;
use App\Entity\Data\TimeFrames;
use App\Model\BotAlgorithmManager;
use App\Service\TechnicalAnalysis\Strategies;
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
    public function getChart(Request $request, int $id)
    {
        $timeFrame = $request->query->get('timeframe');
        $startTime = $request->query->get('start');
        $currencyPairRepo = $this->getDoctrine()
            ->getRepository(CurrencyPair::class);

        /** @var CurrencyPair $currencyPair */
        $currencyPair = $currencyPairRepo
            ->find($id);

        $candles = $currencyPairRepo->getCandlesByTimeFrame($currencyPair, $timeFrame, $startTime);
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

        $testResult = $this->manager->runTest($algo, TestTypes::STANDARD_TEST, $startTime, $endTime);

        return $this->render('algo_test_result.html.twig', [
            "pair" => $algo->getCurrencyPair(),
            "trades" => $testResult->getTrades()
        ]);
    }

    /**
     * @Route("/algos/external_indicator/result", name="run_external_indicator_test", methods={"GET"})
     * @return Response
     * @throws \Exception
     */
    public function runExternalIndicatorTest(Request $request)
    {
        $id = $request->query->get('pair_id');
        $indicatorType = $request->query->get('indicator_type');
        $startTime = $request->query->get('start_time');
        $endTime = $request->query->get('end_time');

        /** @var ExternalIndicatorDataType $type */
        $type = $this->getDoctrine()
            ->getRepository(ExternalIndicatorDataType::class)
            ->findOneBy(['name' => $indicatorType]);

        if(!$type) {
            return $this->render('error.html.twig', [
                "error" => "External indicator not found.",
            ]);
        }

        /** @var CurrencyPair $pair */
        $pair = $this->getDoctrine()
            ->getRepository(CurrencyPair::class)
            ->find($id);

        if(!$pair) {
            return $this->render('error.html.twig', [
                "error" => "Pair not found.",
            ]);
        }

        $trades = $this->manager->runExternalIndicatorTest($type, $pair, $startTime, $endTime);

        return $this->render('algo_test_result.html.twig', [
            "pair" => $pair,
            "trades" => $trades
        ]);
    }

    /**
     * @Route("/algos/trendlines/result", name="run_trend_line_test", methods={"GET"})
     * @return Response
     * @throws \Exception
     */
    public function runTrendLinesTest(Request $request)
    {
        $id = $request->query->get('pair_id');
        $startTime = $request->query->get('start_time');
        $endTime = $request->query->get('end_time');
        $timeFrame = $request->query->get('timeframe');


        /** @var CurrencyPair $pair */
        $pair = $this->getDoctrine()
            ->getRepository(CurrencyPair::class)
            ->find($id);

        if(!$pair) {
            return $this->render('error.html.twig', [
                "error" => "Pair not found.",
            ]);
        }

        $trendLines = $this->manager->runTrendLinesTest($pair, $timeFrame, $startTime, $endTime);

        return $this->render('algo_test_result.html.twig', [
            "pair" => $pair,
            "trades" => [],
            "trend_lines" => $trendLines
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
            $algo->setEntryStrategy($request->request->get('strategy'));
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
        $algo->setEntryStrategy($request->request->get('strategy'));
        $algo->setStopLoss($request->request->get('stop_loss'));
        $algo->setTakeProfit($request->request->get('take_profit'));
        $algo->setObservations($request->request->get('observations'));

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($algo);
        $entityManager->flush();

        return $this->redirectToRoute('list_algos');
    }
}