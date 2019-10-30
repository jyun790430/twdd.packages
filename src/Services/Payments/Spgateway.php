<?php


namespace Twdd\Services\Payments;


use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Twdd\Mail\System\InfoAdminMail;
use Twdd\Repositories\DriverMerchantRepository;
use Twdd\Repositories\MemberCreditCardRepository;
use Zhyu\Facades\ZhyuCurl;

class Spgateway extends PaymentAbstract implements PaymentInterface
{
    private $driverMerchant;
    private $memberCreditCard;

    public function back(){

    }

    public function cancel(){

    }

    public function pay(array $params = []){
        $this->preInit();

        $payer_email = isset($params['payer_email']) ? $params['payer_email'] : $this->getMemberCreditCard()->CardHolder;
        $is_random_serial = isset($params['is_random_serial']) ? $params['is_random_serial'] : false;
        $OrderNo = $this->getOrderNo($is_random_serial);

        if(strlen($payer_email)==0){

            return $this->returnError($OrderNo, 2001, '驗證錯誤');
        }
        $money = $this->getMoney();

        if(strlen($money)==0){

            return $this->returnError($OrderNo, 2002, '驗證錯誤');
        }

        $datas = [
            'TimeStamp'         =>  time(),
            'Version'           =>  '1.0',
            'MerchantOrderNo'   =>  $OrderNo,
            'Amt'               =>  $money,
            'ProdDesc'          =>  '代駕費用',
            'PayerEmail'        =>  $payer_email,
            'TokenValue'        =>  $this->memberCreditCard->TokenValue,
            'TokenTerm'         =>  $this->task->member_id,
            'TokenSwitch'       =>  'on',
        ];

        try{
            $url = env('SPGATEWAY_URL');
            $res = $this->post($url, $this->preparePostData($datas));
            if(isset($res->Status) && $res->Status=='SUCCESS') {
                Log::info('刷卡成功 (單號：' . $this->task->id . '): ', [$res]);

                return $this->returnSuccess($OrderNo, $msg, $res);
            }else{
                $msg = '刷卡失敗 (單號：' . $this->task->id . ')';
                Log::info($msg.': ', [$res]);
                $this->mail(new InfoAdminMail('［系統通知］智付通，刷卡失敗', $msg, $res));

                return $this->returnError($OrderNo, 2003, $msg, $res);
            }
        }catch(\Exception $e){
            $msg = '刷卡異常 (單號：'.$this->task->id.'): '.$e->getMessage();
            Log::info($msg);
            Bugsnag::notifyException($e);
            $this->mail(new InfoAdminMail('［系統通知］!!!智付通，刷卡異常!!!', $msg));

            return $this->returnError($OrderNo, 500, $msg);
        }
    }

    private function mail(InfoAdminMail $infoAdminMail){
        $emails = explode(',', env('ADMIN_NOTIFY_EMAilS', 'service@twdd.com.tw'));
        if(count($emails)) {
            Mail::to($emails)->queue($infoAdminMail);
        }
    }

    public function query(){

    }


    public function getDriverMerchant(){

        return $this->driverMerchant;
    }

    public function getMemberCreditCard(){

        return $this->memberCreditCard;
    }

    private function preInit(){
        $this->driverMerchant = app(DriverMerchantRepository::class)->findByTaskId($this->task);
        $this->memberCreditCard = app(MemberCreditCardRepository::class)->findByTaskId($this->task);
    }



    private function preparePostData(array $datas){
        $post_data_str = http_build_query($datas);
        $encrypt_data = $this->spgateway_encrypt($post_data_str);

        $postData = [
            'MerchantID_'   =>  $this->driverMerchant->MerchantID,
            'Pos_'   =>  'JSON',
            'PostData_' =>  $encrypt_data,
        ];

        return $postData;
    }

    private function spgateway_encrypt($str = "") {
        $str = trim(bin2hex( openssl_encrypt($this->addPadding($str), 'aes-256-cbc', $this->driverMerchant->MerchantHashKey, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $this->driverMerchant->MerchantIvKey) ));

        return $str;
    }

    function addPadding($string, $blocksize = 32) {
        $len = strlen($string);
        $pad = $blocksize - ($len % $blocksize);
        $string .= str_repeat(chr($pad), $pad);

        return $string;
    }

    function post(string $url, array $postData){
        if(strlen($url)==0){

            throw new \Exception('Please set SPGATEWAY_URL value in .env');
        }
        $res = ZhyuCurl::url($url)->post($postData);

        return json_decode($res);
    }

}