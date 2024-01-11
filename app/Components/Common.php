<?php

namespace App\Components;

use Exception;
use App\Model\Device;
use Illuminate\Support\Facades\Mail;

class Common
{

    public static function restartdevice($try_count = 0)
    {

        $device = Device::where('status', 1)->get();

        foreach ($device as $key => $Data) {

            $Data->device_status = 'offline';
            $Data->save();

            try {
                $rawdata = [
                    "SearchDescription" => [
                        "position"  => 0,
                        "maxResult" => 100,
                        "Filter"    => [
                            "key"          => $Data->ip,
                            "devType"      => "AccessControl",
                            "protocolType" => ["ISAPI"],
                            "devStatus"    => ["online", "offline"],
                        ],
                    ],
                ];

                $client   = new \GuzzleHttp\Client();
                $response = $client->request('POST', 'http://localhost:' . $Data->port . '/' . $Data->protocol . '/ContentMgmt/DeviceMgmt/deviceList?format=json', [
                    'auth' => [$Data->username, $Data->password, "digest"],
                    'json' => $rawdata,
                ]);

                $statusCode = $response->getStatusCode();
                $content    = $response->getBody()->getContents();
                $data       = json_decode($content);
                //dd($data);
                if ($data->SearchResult->numOfMatches == 1) {
                    $deviceInfo          = $data->SearchResult->MatchList[0]->Device;
                    $Data->model         = $deviceInfo->devMode;
                    $Data->device_status = $deviceInfo->devStatus;

                    if ($Data->verification_status == 0 && $Data->device_status == "online") {
                        $Data->verification_status = 1;
                    }

                    $Data->save();
                }
            } catch (\Exception $e) {
                //return redirect()->back()->with('error', 'Something went wrong try again ! ');
            }
        }

        $offline_device = Device::where('device_status', 'offline')->where('status', '!=', 2)->get();

        //dd(count($offline_device) , count($device));

        if (count($offline_device) == count($device)) {
            if ($try_count == 0) {
                $out = exec('C:\Program Files\AC Gateway\Guard\stop.bat', $output, $return);
                $out = exec('C:\Program Files\AC Gateway\Guard\start.bat', $output, $return);
                if ($return == 0) {
                    sleep(20);
                    return Common::restartdevice($try_count + 1);
                } else {
                    return Common::restartdevice($try_count + 1);
                }
            } elseif ($try_count < 6) {
                return Common::restartdevice($try_count + 1);
            } elseif ($try_count >= 6) {
                return json_encode(["status" => "all_offline_check_cable", 'msg' => 'All the devices are offline. Please check the network connection !']);
            }
        } else {
            $online_device = Device::where('device_status', 'online')->where('status', '!=', 2)->count();
            if ($online_device != count($device)) {
                //\Log::info($try_count);
                if ($try_count < 6) {
                    sleep(7);
                    return Common::restartdevice($try_count + 1);
                } else {
                    if (count($offline_device)) {
                        $offline_set = [];
                        foreach ($offline_device as $offlineData) {
                            $offline_set[] = $offlineData->name . " ( " . $offlineData->model . " )";
                        }
                        $offlineDevice = implode(", ", $offline_set);
                        return json_encode(["status" => "some_offline", "offline_device" => $offlineDevice, 'msg' => 'The following device(s) are not reachable / offline , so unable to sync. Please check the device connection.The offline Devices are : [ ' . $offlineDevice . ' ]']);
                    } else {
                        return json_encode(["status" => "all_online"]);
                    }
                }
            } else {

                if (count($offline_device)) {
                    $offline_set = [];
                    foreach ($offline_device as $offlineData) {
                        $offline_set[] = $offlineData->name . " ( " . $offlineData->model . " )";
                    }
                    $offlineDevice = implode(", ", $offline_set);
                    return json_encode(["status" => "some_offline", "offline_device" => $offlineDevice, 'msg' => 'The following device(s) are not reachable / offline , so unable to sync. Please check the device connection.The offline Devices are : [ ' . $offlineDevice . ' ]']);
                } else {
                    return json_encode(["status" => "all_online"]);
                }
            }
        }
    }

    public static function clearinternalerror()
    {
        $out = exec('C:\Program Files\AC Gateway\Guard\stop.bat', $output, $return);
        $out = exec('C:\Program Files\AC Gateway\Guard\start.bat', $output, $return);
        sleep(15);
        return true;
    }


    public static function triggerException()
    {
        // using throw keyword
        throw new Exception('Client error:"POSThttp://localhost/ISAP/AccesCantrel/AcsEventformat-json&deyindex=69006054-1770-447-8569-5608A735076 resulted in a `403 Forbidden` response: {"errorCode":805306388."errorMsg":"Internal error.","statusCode":3,"statusString":"Device Error"');
    }

    public static function errormsg()
    {
        return "Device not responding. Please navigate to Device Configuration and click Refresh device service button.";
    }


    public static function liveurl()
    {
        // tata smartfood
        // return "https://ipro-people.com/tatasmartfoodz/api/";
        return "https://localhost/tatasmartfoodz/api/";
    }

    
    public static function mail($template,$to,$subject,$data){
		$mail = Mail::send($template,$data, function ($message) use ($to,$subject) {
			$message->from('ebulientcatcoc01@gmail.com', 'Bafna Pharmacy');
			$message->to($to, 'Bafna Pharmacy')->subject($subject);
		});
	}
}
