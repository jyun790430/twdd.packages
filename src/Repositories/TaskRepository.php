<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-03-20
 * Time: 15:02
 */

namespace Twdd\Repositories;


use Illuminate\Support\Facades\DB;
use Twdd\Criterias\Task\TaskStateInProcess;


use Twdd\Models\Task;
use Zhyu\Repositories\Eloquents\Repository;

class TaskRepository extends Repository
{

    public function model()
    {
        return Task::class;
    }

    public function checkNotHaveInProcessTaskByMemberId($member_id){

        $taskStateUnder3 = app()->make(TaskStateInProcess::class);
        $this->pushCriteria($taskStateUnder3);
        $tasks = $this->findWhere([
            'member_id' => $member_id,
        ], ['id']);

        if(count($tasks)>0){

            return true;
        }

        return false;
    }

    public function checkNotHaveInProcessTaskByDriverId($driver_id){

        $taskStateUnder3 = app()->make(TaskStateInProcess::class);
        $this->pushCriteria($taskStateUnder3);
        $tasks = $this->findWhere([
            'driver_id' => $driver_id,
        ], ['id']);

        if(count($tasks)>0){

            return true;
        }

        return false;
    }

    public function nums7ByUserCreditCodeAndMember(string $UserCreditCode, int $member_id){

        return $this->where('UserCreditCode', $UserCreditCode)->where('TaskState', 7)->where('member_id', $member_id)->count();
    }

    public function isPay(int $id, int $TaskFee, int $twddFee, int $is_first_use = 0, int $member_creditcard_id = 0){
        $params = [
            'is_pay' => 1,
            'is_first_use' => $is_first_use,
            'twddFee' => $twddFee,
            'TaskFee' => $TaskFee,
            'member_creditcard_id' => $member_creditcard_id,
        ];

        return $this->update($id, $params);
    }

    public function view4push2member($id){

        $row = $this->join('driver', 'task.driver_id', '=', 'Driver.id')
            ->join('member', 'task.member_id', '=', 'member.id')
            ->join('driver_location', 'driver.id', '=', 'driver_location.driver_id')
            ->leftJoin('calldriver_task_map','task.id', '=', 'calldriver_task_map.task_id')
            ->leftJoin('member_grade', 'member.member_grade_id', '=', 'member_grade.id')
            //---客人評價司機
            ->leftJoin('DriverTaskExperience', 'task.id', '=', 'DriverTaskExperience.task_id')
            //---司機評價客人
            ->leftJoin('member_score', 'task.id', '=', 'member_score.task_id')
            ->where('task.id', '=', $id)
            ->select(DB::raw('calldriver_task_map.id as map_id'), DB::raw('LPAD(LTRIM(CAST(task.id AS CHAR)), 8, \'0\') as TaskNo'), 'task.id', 'task.TaskState', 'task.createtime', 'task.TaskFee', 'task.TaskDistance', 'task.TaskRideTS', 'TaskArriveTS' ,'TaskStartTS', 'TaskEndTS', 'TaskWaitInterval',
                'UserLat', 'UserLon', 'TaskStartAddress', 'TaskEndAddress', 'TaskWaitTimeFee', 'TaskStartFee','TaskDistanceFee', 'UserCreditCode', 'UserCreditValue', 'type', 'task.pay_type', 'task.call_type', 'task.UserRemark', 'task.matchDistance',
                'task.call_far_driver', DB::raw('driver.id as driver_id'), DB::raw('member.id as member_id'), 'DriverID', 'DriverName', 'UserCity', 'UserDistrict', 'UserAddress', 'UserAddressKey', 'driver.DriverPhoto', 'driver.DriverServiceTime',
                'driver.DriverRating', DB::raw('CEILING((UNIX_TIMESTAMP()-UNIX_TIMESTAMP(driver.DriverDrivingSeniorityDate))/3600/24/365) as DriverDrivingSeniorityYear'), 'driver.DriverDrivingSeniorityDate', 'DriverLat', 'DriverLon', 'DestCity', 'DestDistrict', 'DestAddress', 'DestAddressKey', 'extra_price', 'calldriver_task_map.call_driver_id',
                'calldriver_task_map.is_cancel', DB::raw('member_score.id as experience_id'), 'task.depot_user_id', 'task.is_receive_money_first', 'cash_fee_discount', 'creditcard_fee_discount', 'can_not_use_coupon', 'task.is_used_gold', 'task.is_quick_match_by_driver'
                , 'TaskCreditCode', DB::raw('left(member.UserName, 1) as UserName'), 'member.UserGender', 'member.UserPhone', 'member.UserEmail', 'member.member_grade_id', 'calldriver_id'
            )
            ->first();

        return $row;
    }

    public function view4push2driver($id){

        $qb = $this->join('calldriver_task_map', 'calldriver_task_map.task_id', '=', 'task.id')
            ->join('member', 'task.member_id', '=', 'member.id')
            ->leftJoin('member_grade', 'member.member_grade_id', '=', 'member_grade.id')
            ->leftJoin('member_push', 'task.member_id', '=', 'member_push.member_id')
            ->Join('driver_location', 'driver.id', '=', 'driver_location.driver_id')
            ->where('task.id', '=', $id);

        $row = $qb->select(
            DB::raw('LPAD(LTRIM(CAST(task.id AS CHAR)), 8, \'0\') as TaskNo'),
            'task.id',
            'task.TaskState',
            'task.createtime',
            'task.TaskFee',
            'task.TaskDistance' ,
            'TaskArriveTS',
            'TaskStartTS',
            'TaskEndTS',
            'TaskWaitInterval',
            'TaskCreditCode',
            'TaskStartAddress',
            'TaskEndAddress',
            'TaskWaitTimeFee',
            'TaskStartFee',
            'TaskDistanceFee',
            'UserCreditCode',
            'UserCreditValue',
            'type', 'task.pay_type',
            'call_type',
            DB::raw('left(member.UserName, 1) as UserName'),
            'member.UserGender',
            'task.UserRemark',
            'task.matchDistance',
            'task.call_far_driver',
            'member.member_grade_id',
            'cash_fee_discount',
            'creditcard_fee_discount',
            'can_not_use_coupon',
            'member.UserPhone',
            'member.UserEmail',
            DB::raw('driver.id as driver_id'),
            DB::raw('member.id as member_id'),
            'DriverID',
            'DriverName',
            'DriverGender',
            'DriverPhoto',
            'driver.DriverServiceTime',
            'driver.DriverRating',
            'DriverDrivingSeniorityDate',
            'UserLat',
            'UserLon',
            'UserCity',
            'UserDistrict',
            'UserAddress',
            'UserAddressKey',
            'DestAddress',
            'DestAddressKey',
            'extra_price', 'over_price', 'DestCity', 'DestDistrict', 'DestAddress', 'DriverLat', 'DriverLon', DB::raw('CEILING((UNIX_TIMESTAMP()-UNIX_TIMESTAMP(Driver.DriverDrivingSeniorityDate))/3600/24/365) as DriverDrivingSeniorityYear'),
            'member_push.DeviceType', 'task.depot_user_id', 'task.is_receive_money_first', 'task.callback_url', 'call_member_id'
        )
            ->first();

        $H = DB::table('calldriver_history_map')->where('task_id', '=', $id)->select('TS')->first();
        $row->TSsend = isset($H->TS) ?  $H->TS  :   time();

        return $row;
    }

    public function lastTaskByDriverId(int $driver_id, array $columns = ['*']){

        return $this->select($columns)->where('driver_id', $driver_id)->orderby('id', 'desc')->first();
    }

}
