davisweather.api
================

PHP Class to communicate with Davis Instruments, IP Interface and pull Weather Data 


Example Code

$davis = new DavisWeather();

$weather = $davis->getWeatherLive($IP,$PORT);

if ($weather == false)
		    {
		    	$Fail = True;
		    }
		    elseif ($weather->Barometer == 0)
		    {
		    	$Fail = True;
		    }
		    else
		    {
		    	$Fail = false;
		    }
		    
if (!$Fail)
{
/*

List of Data Received

$weather->Bar_Trend,
$weather->Barometer,
$weather->Indoor->Temp,
$weather->Indoor->Humidity,
$weather->Outdoor->Temp,
$weather->Outdoor->Humidity,
$weather->Wind->Speed,
$weather->Wind->Direction,
$weather->Wind->Speed_10Avg,
$weather->Wind->Speed_2Avg,
$weather->Wind->Wind_Gust->Speed_10Avg,
$weather->Wind->Wind_Gust->Direction_10Avg,
$weather->Calculated->temps->Dew_Point,
$weather->Calculated->temps->Heat_Index,
$weather->Calculated->temps->Wind_Chill,
$weather->Rain->RainUnits,
$weather->Rain->RainRatePerHr,
$weather->Rain->Storm->Rain,
$weather->Rain->Storm->DateStart,
$weather->Rain->historical->DailyRain,
$weather->Rain->historical->DailyET,
$weather->Rain->historical->Last15MinRain,
$weather->Rain->historical->LastHourRain,
$weather->Rain->historical->Last24Rain
				                    
*/
}

or use

$davis->setLamp($Status, $IP, $PORT)  //$Status = 0 off, 1 On
		    
