<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    const FLAGGED_YES = 'yes';
    const FLAGGED_NO = 'no';
    const MANUALLY_HANDLED_YES = 'yes';
    const MANUALLY_HANDLED_NO = 'no';
    const BY_ADMIN_YES = 'yes';
    const BY_ADMIN_NO = 'no';

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $userId = $request->get('user_id');

        if ($userId) {
            return response($this->repository->getUsersJobs($userId));
        }

        if ($request->__authenticatedUser->isAdmin()) {
            return response($this->repository->getAll($request));
        }

        return response([], 403); // Unauthorized response
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $job = $this->repository->with('translatorJobRel.user')->find($id);

        return response($job);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $this->validateJobRequest($request);

        $data = $request->all();
        $response = $this->repository->store($request->__authenticatedUser, $data);

        return response($response);
    }

    private function validateJobRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_name' => 'required|string',
            'job_description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()], 422);
        }
    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        $data = $request->all();
        $cuser = $request->__authenticatedUser;
        $response = $this->repository->updateJob($id, array_except($data, ['_token', 'submit']), $cuser);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->storeJobEmail($data);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        if($user_id = $request->get('user_id')) {

            $response = $this->repository->getUsersJobsHistory($user_id, $request);
            return response($response);
        }

        return null;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJob($data, $user);

        return response($response);
    }

    public function acceptJobWithId(Request $request)
    {
        $data = $request->get('job_id');
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJobWithId($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->cancelJobAjax($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->endJob($data);

        return response($response);

    }

    public function customerNotCall(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->customerNotCall($data);

        return response($response);

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $user = $request->__authenticatedUser;

        $response = $this->repository->getPotentialJobs($user);

        return response($response);
    }

    public function distanceFeed(Request $request)
    {
        $data = $request->all();
        $this->validateDistanceData($request);

        DB::transaction(function () use ($data) {
            $this->updateJobDistance($data);
            $this->updateJobDetails($data);
        });

        return response('Record updated!');
    }

    private function validateDistanceData($request)
    {
        $validator = Validator::make($request->all(), [
            'distance' => 'nullable|numeric',
            'time' => 'nullable|date_format:H:i',
            'jobid' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }
    }

    /**
     * Update the distance and time information for a specific job.
     *
     * @param array
     * @return void
     */

    private function updateJobDistance($data)
    {
        Distance::where('job_id', $data['jobid'])->update([
            'distance' => $data['distance'] ?? null,
            'time' => $data['time'] ?? null,
        ]);
    }

    /**
     * Update the job details, including admin comments, flagged status, session time, and other attributes.
     *
     * @param array $data
     * @param int $jobid
     * @return void
     */

    private function updateJobDetails($data)
    {
        Job::where('id', $data['jobid'])->update([
            'admin_comments' => $data['admincomment'] ?? '',
            'flagged' => $data['flagged'] === 'true' ? self::FLAGGED_YES : self::FLAGGED_NO,
            'session_time' => $data['session_time'] ?? '',
            'manually_handled' => $data['manually_handled'] === 'true' ? self::MANUALLY_HANDLED_YES : self::MANUALLY_HANDLED_NO,
            'by_admin' => $data['by_admin'] === 'true' ? self::BY_ADMIN_YES : self::BY_ADMIN_NO,
        ]);
    }

    public function reopen(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->reopen($data);

        return response($response);
    }

    /**
     * Reopen a job that was previously closed or completed.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */

    public function resendNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);
        $this->repository->sendNotificationTranslator($job, $job_data, '*');

        return response(['success' => 'Push sent']);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }

}
