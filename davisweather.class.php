<?php
//Version 1.0
define('ASCII_LINE_FEED', chr(10));
define('ASCII_CARRIAGE_RETURN', chr(13));
define('ASCII_ACK', chr(6));
define('RESPONSE_LOOP', 1);
define('RESPONSE_OK', 2);

class DavisWeather
{

    //Set Lamp On/Off
    //Status = 0 = OFF, 1 = ON
    public function setLamp($Status)
	{
		$Response = $this->pullPacket("LAMPS " . $Status . ASCII_LINE_FEED, RESPONSE_OK);
		return $Response;
	}

	public function getWeatherLive($IP, $PORT)
	{
		$RainRate = .01;
		$Packet =  $this->pullPacket("LPS 2 1" . ASCII_LINE_FEED, RESPONSE_LOOP, $IP, $PORT);
		if ($Packet == false)
		{
			return false;
		}

        //LOOP 2 Packet 99 Bytes
		$DataPacket = (object) array(
								'Bar_Trend' => $this->BarTrend($this->ConvertValue($Packet[3])),
								'Barometer' => $this->ConvertValue($Packet[7], false, $Packet[8], 3),
								'Indoor' => (object) array (
														'Temp'  => $this->ConvertValue($Packet[9], true, $Packet[10], 1),
														'TempUnits' => 'º F',
														'Humidity' => $this->ConvertValue($Packet[11]),
														'HumidityUnits' => '%'
														),
								'Outdoor' => (object) array (
														'Temp'  => $this->ConvertValue($Packet[12], true, $Packet[13], 1),
														'TempUnits' => 'º F',
														'Humidity' => $this->ConvertValue($Packet[33]),
														'HumidityUnits' => '%'
														),
								'Wind' => (object) array (
														'SpeedUnits' => 'MPH',
														'Speed' => $this->ConvertValue($Packet[14]),
														'Speed_10Avg' => $this->ConvertValue($Packet[18], false, $Packet[19], 1),
														'Speed_2Avg' => $this->ConvertValue($Packet[20], false, $Packet[21], 1),
														'DirectionUnits' => 'º',
														'Direction' => $this->ConvertValue($Packet[16], false, $Packet[17]),
														'Wind_Gust' => (object) array(
																				'Speed_10Avg' => $this->ConvertValue($Packet[22], false, $Packet[23], 1),
																				'Direction_10Avg' => $this->ConvertValue($Packet[24], false, $Packet[25])
																				)
														),
								'Calculated' => (object) array (
														'temps' => (object) array (
																			'TempUnits' => 'º F',
																			'Dew_Point' => (integer) $this->ConvertValue($Packet[30], true, $Packet[31]),
																			'Heat_Index' => (integer) $this->ConvertValue($Packet[35], true, $Packet[36]),
																			'Wind_Chill' =>  (integer) $this->ConvertValue($Packet[37], true, $Packet[38])
																			)
																),
								'Rain' => (object) array (
														'RainUnits' => "in",
														'RainRatePerHr' => ($this->ConvertValue($Packet[41], true, $Packet[42]) * $RainRate),

														'Storm' => (object) array (
																				'Rain' => ($this->ConvertValue($Packet[46], true, $Packet[47]) * $RainRate),
																				'DateStart' => $this->ConvertBITDate($this->ConvertValue($Packet[48], true, $Packet[49]))
																				),
														'historical' => (object) array (
																				'DailyRain' => ($this->ConvertValue($Packet[50], true, $Packet[51]) * $RainRate),
																				'DailyET' => (integer) $this->ConvertValue($Packet[56], true, $Packet[57]),
																				'Last15MinRain' => ($this->ConvertValue($Packet[52], true, $Packet[53]) * $RainRate),
																				'LastHourRain' =>  ($this->ConvertValue($Packet[54], true, $Packet[55]) * $RainRate),
																				'Last24Rain' => ($this->ConvertValue($Packet[58], true, $Packet[59]) * $RainRate)
																				)
														)
								);

		return $DataPacket;

	}

//Private Functions

	private function ConvertBITDate($Value)
	{

		$bits = decbin($Value);
		$bits = str_repeat("0", (16 - strlen($bits))) . $bits;
		return (2000 + bindec(substr($bits,10,7))) . "-" . str_pad((int) bindec(substr($bits,0,4)) , 2,"0",STR_PAD_LEFT) . "-" . str_pad((int) bindec(substr($bits,4,5)) , 2,"0",STR_PAD_LEFT) . " 00:00:00";

	}
	private function ConvertValue($Byte1, $Neg=false, $Byte2=null, $DecPt=null)
	{
		if(!is_null($Byte2))
		{
			if($Neg)
			{
				$Value = sprintf("%d", (ord($Byte1) + (ord($Byte2)*256)));
	        }
	        else
	        {
	        	$Value = (ord($Byte1) + (ord($Byte2)*256));
	        }
	        if(!is_null($DecPt))
	        {
            	return $Value / ((1 . str_repeat("0", $DecPt))*1);
	        }
	        else
	        {
	        	return $Value;
	        }
	     }
	     else
	     {
	     	return ord($Byte1);
	     }

	}

    private function BarTrend($Byte)
    {
    	switch ($Byte)
    	{
    		case 196:
    			return "Falling Rapidly";
    			break;

    		case 236:
    			return "Falling Slowly";
    			break;

    		case 0:
    			return "Steady";
    			break;

    		case 20:
    			return "Rising Slowly";
    			break;

    		case 60:
    			return "Rising Rapidly";
    			break;

    		default:
    			return "NO DATA";
    			break;
    	}
    }

	private function pullPacket($Command, $ACK, $IP, $PORT)
	{
	    $fp = fsockopen($IP, $PORT, $errno, $errstr, 5);
	    if (!$fp)
		{
			openlog("[HomeAutoFeed] " . $GLOBALS['PATHVar'] . "[" . getmypid() . "]", 0, LOG_LOCAL0);
			syslog(LOG_ERR, "Davis Weather Class - Connection Fail [" . $IP . ":" . $PORT . "][[$errstr ($errno)]");
  			closelog();
	    	//echo "Davis Weather Class - Connection Fail [" . WEATHERIP . ":" . WEATHERPORT . "][[$errstr ($errno)]";
		}
		else
		{
			//Wake Unit Up
			//Loop 3 Times w/ 2 sec pause till you get response
			$check = false;
			$loop = 1;
			while (!$check)
			{
				fwrite($fp, ASCII_LINE_FEED);
				$response = fgetc($fp);
				if (ord($response) == ord(ASCII_LINE_FEED))
				{
					$response = fgetc($fp);
					if (ord($response) == ord(ASCII_CARRIAGE_RETURN))
					{
						$check=true;
					}
				}
				if (!$check)
				{
					if ($loop == 3)
					{
						return "";
					}
					$loop++;
					sleep(2);
				}
			}

			//If Unit Responded Continue
            if ($check)
            {
            	fwrite($fp, $Command);
            	if ($ACK == RESPONSE_OK)
            	{
            		$response = fread($fp,6);
            		if ($response == (ASCII_LINE_FEED . ASCII_CARRIAGE_RETURN . "OK" . ASCII_LINE_FEED . ASCII_CARRIAGE_RETURN))
            		{
            			fclose($fp);
            			return true;
            		}
            		else
            		{
            			fclose($fp);
            			return false;
            		}
            	}
            	if ($ACK == RESPONSE_LOOP)
            	{
            		$response = fgetc($fp);
            		if (ORD($response) == ORD(ASCII_ACK))
                    {
                    	$packet = array();
                    	for ($pointer = 0; $pointer < 99; $pointer++)
	    				{
	        				$packet[$pointer] = fgetc($fp);
	        				//echo ".";
	        			}
                        return $packet;


                    }


            	}
            }
            else
            // Error out close port and return fail
            {
            	fclose($fp);
            	return false;
            }

	    }
	}


}


?>
