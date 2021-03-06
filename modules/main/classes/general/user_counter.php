<?
class CAllUserCounter
{
	protected static $counters = false;

	
	/**
	* <p>Функция предназначена для получения определенного счетчика пользователя.</p>
	*
	*
	*
	*
	* @param user_i $d  Идентификатор пользователя
	*
	*
	*
	* @param cod $e  Код счётчика
	*
	*
	*
	* @param site_i $d = SITE_ID Идентификатор сайта, необязательный параметр. По умолчанию
	* подставляется текущий сайт.
	*
	*
	*
	* @return mixed <p>Возвращаются данные определенного счетчика пользователя.</p> <a
	* name="examples"></a>
	*
	*
	* <h4>Example</h4> 
	* <pre>
	* echo $code = CUserCounter::GetValue($USER-&gt;GetID(), 'code1');
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cusercounter/getvalue.php
	* @author Bitrix
	*/
	public static function GetValue($user_id, $code, $site_id = SITE_ID)
	{
		$user_id = intval($user_id);

		if ($user_id <= 0)
			return false;

		$arCodes = self::GetValues($user_id, $site_id);
		if (isset($arCodes[$code]))
			return intval($arCodes[$code]);
		else
			return 0;
	}

	
	/**
	* <p>Функция позволяет получить список всех счетчиков пользователя.</p>
	*
	*
	*
	*
	* @param $user_i $d  Идентификатор пользователя, обязательный параметр.
	*
	*
	*
	* @param $site_i $d = SITE_ID Идентификатор сайта, необязательный параметр. По умолчанию
	* принимает значение текущего сайта.
	*
	*
	*
	* @return mixed <p>Список счётчиков конкретного пользователя на сайте.</p> <a
	* name="examples"></a>
	*
	*
	* <h4>Example</h4> 
	* <pre>
	* $codes = CUserCounter::GetValues($USER-&gt;GetID(), SITE_ID);
	* echo '&lt;pre&gt;'.print_r($codes, 1).'&lt;/pre&gt;';
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cusercounter/getvalues.php
	* @author Bitrix
	*/
	public static function GetValues($user_id, $site_id = SITE_ID)
	{
		global $DB, $CACHE_MANAGER;

		$user_id = intval($user_id);
		if ($user_id <= 0)
			return array();

		if(!isset(self::$counters[$user_id][$site_id]))
		{
			if (
				CACHED_b_user_counter !== false
				&& $CACHE_MANAGER->Read(CACHED_b_user_counter, "user_counter".$user_id, "user_counter")
			)
			{
				$arAll = $CACHE_MANAGER->Get("user_counter".$user_id);
				if(is_array($arAll))
				{
					foreach($arAll as $arItem)
					{
						if ($arItem["SITE_ID"] == $site_id || $arItem["SITE_ID"] == '**')
						{
							if (!isset(self::$counters[$user_id][$site_id][$arItem["CODE"]]))
								self::$counters[$user_id][$site_id][$arItem["CODE"]] = 0;
							self::$counters[$user_id][$site_id][$arItem["CODE"]] += $arItem["CNT"];
						}
					}
				}
			}
			else
			{
				$strSQL = "
					SELECT CODE, SITE_ID, CNT
					FROM b_user_counter
					WHERE USER_ID = ".$user_id;

				$dbRes = $DB->Query($strSQL, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$arAll = array();
				while ($arRes = $dbRes->Fetch())
				{
					$arAll[] = $arRes;
					if ($arRes["SITE_ID"] == $site_id || $arRes["SITE_ID"] == '**')
					{
						if (!isset(self::$counters[$user_id][$site_id][$arRes["CODE"]]))
							self::$counters[$user_id][$site_id][$arRes["CODE"]] = 0;
						self::$counters[$user_id][$site_id][$arRes["CODE"]] += $arRes["CNT"];
					}
				}
				if (CACHED_b_user_counter !== false)
					$CACHE_MANAGER->Set("user_counter".$user_id, $arAll);
			}
		}

		return self::$counters[$user_id][$site_id];
	}

	
	/**
	* <p>Функция позволяет получить все значения для всех доступных сайтов.</p>
	*
	*
	*
	*
	* @param $user_i $d  Идентификатор пользователя, обязательный параметр.
	*
	*
	*
	* @return mixed <p>Возвращает все значения для всех доступных сайтов.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cusercounter/getallvalues.php
	* @author Bitrix
	*/
	public static function GetAllValues($user_id)
	{
		global $DB, $CACHE_MANAGER;

		$arCounters = Array();
		$user_id = intval($user_id);
		if ($user_id <= 0)
			return $arCounters;

		$arSites = Array();
		$res = CSite::GetList(($b = ""), ($o = ""), Array("ACTIVE" => "Y"));
		while ($row = $res->Fetch())
			$arSites[] = $row['ID'];

		if (CACHED_b_user_counter !== false && $CACHE_MANAGER->Read(CACHED_b_user_counter, "user_counter".$user_id, "user_counter"))
		{
			$arAll = $CACHE_MANAGER->Get("user_counter".$user_id);
		}
		else
		{
			$strSQL = "
				SELECT CODE, SITE_ID, CNT
				FROM b_user_counter
				WHERE USER_ID = ".$user_id;

			$dbRes = $DB->Query($strSQL, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$arAll = array();
			while ($arRes = $dbRes->Fetch())
				$arAll[] = $arRes;

			if (CACHED_b_user_counter !== false)
				$CACHE_MANAGER->Set("user_counter".$user_id, $arAll);
		}

		foreach($arAll as $arItem)
		{
			if ($arItem['SITE_ID'] == '**')
			{
				foreach ($arSites as $siteId)
				{
					if (isset($arCounters[$siteId][$arItem['CODE']]))
						$arCounters[$siteId][$arItem['CODE']] += intval($arItem['CNT']);
					else
						$arCounters[$siteId][$arItem['CODE']] = intval($arItem['CNT']);
				}
			}
			else
			{
				if (isset($arCounters[$arItem['SITE_ID']][$arItem['CODE']]))
					$arCounters[$arItem['SITE_ID']][$arItem['CODE']] += intval($arItem['CNT']);
				else
					$arCounters[$arItem['SITE_ID']][$arItem['CODE']] = intval($arItem['CNT']);
			}
		}

		return $arCounters;
	}

	public static function GetLastDate($user_id, $code, $site_id = SITE_ID)
	{
		global $DB;

		$user_id = intval($user_id);
		if ($user_id <= 0 || strlen($code) <= 0)
			return 0;

		$strSQL = "
			SELECT ".$DB->DateToCharFunction("LAST_DATE", "FULL")." LAST_DATE
			FROM b_user_counter
			WHERE USER_ID = ".$user_id."
			AND (SITE_ID = '".$site_id."' OR SITE_ID = '**')
			AND CODE = '".$DB->ForSql($code)."'
		";

		$result = 0;
		$dbRes = $DB->Query($strSQL, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
			$result = MakeTimeStamp($arRes["LAST_DATE"]);

		return $result;
	}

	
	/**
	* <p>Функция позволяет обнулить все счётчики пользователя.</p>
	*
	*
	*
	*
	* @param user_i $d  Идентификатор пользователя
	*
	*
	*
	* @param sendPul $l = true Необязательный. Отправлять ли мгновенно данные в модуль
	* <b>Push&amp;Pull</b>, для работы "живых счетчиков" (отправка доступна при
	* установке модуля и активации работы с "Сервером очередей", без
	* сервера очередей работает с версии модуля <b>Push&amp;Pull</b> 12.5.4) Если
	* данный счетчик не требуется пробрасывать, необходимо указать
	* <i>false</i>. По умолчанию <i>true</i>.
	*
	*
	*
	* @return mixed <p>Возвращает <i>true</i>, если действие успешно, <i>false</i> - если нет.</p> <a
	* name="examples"></a>
	*
	*
	* <h4>Example</h4> 
	* <pre>
	* CUserCounter::ClearAll($user_id)
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cusercounter/clearall.php
	* @author Bitrix
	*/
	public static function ClearAll($user_id, $site_id = SITE_ID, $sendPull = true)
	{
		global $DB, $CACHE_MANAGER;

		$user_id = intval($user_id);
		if ($user_id <= 0)
			return false;

		$strSQL = "
			UPDATE b_user_counter SET
			CNT = 0
			WHERE USER_ID = ".$user_id."
			AND (SITE_ID = '".$site_id."' OR SITE_ID = '**')";
		$DB->Query($strSQL, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($site_id == '**')
		{
			if (self::$counters)
				unset(self::$counters[$user_id]);
		}
		else
		{
			if (self::$counters)
				unset(self::$counters[$user_id][$site_id]);
		}

		$CACHE_MANAGER->Clean("user_counter".$user_id, "user_counter");

		if ($sendPull)
			self::SendPullEvent($user_id);

		return true;
	}

	
	/**
	* <p>Метод производит удаление счетчиков по тегу.</p>
	*
	*
	*
	*
	* @param ta $g  Тег, по которому будут удалены счётчики.
	*
	*
	*
	* @param cod $e  Код удаляемого счётчика. </htm
	*
	*
	*
	* @param site_i $d = SITE_ID Необязательный. По умолчанию равен SITE_ID.
	*
	*
	*
	* @param sendPul $l = true Необязательный. Отправлять ли мгновенно данные в модуль
	* <b>Push&amp;Pull</b>, для работы "живых счетчиков" (отправка доступна при
	* установке модуля и активации работы с "Сервером очередей", без
	* сервера очередей работает с версии модуля <b>Push&amp;Pull</b> 12.5.4) Если
	* данный счетчик не требуется пробрасывать, необходимо указать
	* <i>false</i>. По умолчанию <i>true</i>.
	*
	*
	*
	* @return mixed <p>Возвращает <i>true</i> если успешно, <i>false</i> если какая то ошибка.</p> <a
	* name="examples"></a>
	*
	*
	* <h4>Example</h4> 
	* <pre>
	* CUserCounter:: ClearByTag("task_11", "task");
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cusercounter/clearbytag.php
	* @author Bitrix
	*/
	public static function ClearByTag($tag, $code, $site_id = SITE_ID, $sendPull = true)
	{
		global $DB, $CACHE_MANAGER;

		if (strlen($tag) <= 0 || strlen($code) <= 0)
			return false;

		$strSQL = "
			UPDATE b_user_counter SET
			CNT = 0
			WHERE TAG = '".$DB->ForSQL($tag)."' AND CODE = '".$DB->ForSQL($code)."'
			AND (SITE_ID = '".$site_id."' OR SITE_ID = '**')";
		$DB->Query($strSQL, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		self::$counters = false;
		$CACHE_MANAGER->CleanDir("user_counter");

		if ($sendPull && self::CheckLiveMode())
		{
			global $DB;

			$arSites = Array();
			$res = CSite::GetList(($b = ""), ($o = ""), Array("ACTIVE" => "Y"));
			while ($row = $res->Fetch())
				$arSites[] = $row['ID'];

			$strSQL = "
				SELECT pc.CHANNEL_ID, uc.USER_ID, uc.SITE_ID, uc.CODE, uc.CNT
				FROM b_user_counter uc
				INNER JOIN b_pull_channel pc ON pc.USER_ID = uc.USER_ID
				WHERE TAG = '".$DB->ForSQL($tag)."' AND CODE = '".$DB->ForSQL($code)."'
				AND (SITE_ID = '".$site_id."' OR SITE_ID = '**')";

			$res = $DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

			$pullMessage = Array();
			while ($row = $res->Fetch())
			{
				if ($row['SITE_ID'] == '**')
				{
					foreach ($arSites as $siteId)
					{
						if (isset($pullMessage[$row['CHANNEL_ID']][$siteId][$row['CODE']]))
							$pullMessage[$row['CHANNEL_ID']][$siteId][$row['CODE']] += intval($row['CNT']);
						else
							$pullMessage[$row['CHANNEL_ID']][$siteId][$row['CODE']] = intval($row['CNT']);
					}
				}
				else
				{
					if (isset($pullMessage[$row['CHANNEL_ID']][$row['SITE_ID']][$row['CODE']]))
						$pullMessage[$row['CHANNEL_ID']][$row['SITE_ID']][$row['CODE']] += intval($row['CNT']);
					else
						$pullMessage[$row['CHANNEL_ID']][$row['SITE_ID']][$row['CODE']] = intval($row['CNT']);
				}
			}

			foreach ($pullMessage as $channelId => $arMessage)
			{
				CPullStack::AddByChannel($channelId, Array(
					'module_id' => 'main',
					'command'   => 'user_counter',
					'params'    => $arMessage,
				));
			}
		}

		return true;
	}

	protected static function CheckLiveMode()
	{
		return CModule::IncludeModule('pull') && CPullOptions::GetNginxStatus();
	}

	protected static function SendPullEvent($user_id, $code = "")
	{
		$user_id = intval($user_id);
		if ($user_id <= 0)
			return false;

		if (self::CheckLiveMode())
		{
			global $DB;

			$arSites = Array();
			$res = CSite::GetList(($b = ""), ($o = ""), Array("ACTIVE" => "Y"));
			while ($row = $res->Fetch())
				$arSites[] = $row['ID'];

			$strSQL = "
				SELECT pc.CHANNEL_ID, uc.USER_ID, uc.SITE_ID, uc.CODE, uc.CNT
				FROM b_user_counter uc
				INNER JOIN b_pull_channel pc ON pc.USER_ID = uc.USER_ID
				WHERE uc.USER_ID = ".intval($user_id).(strlen($code) > 0? " AND uc.CODE = '".$DB->ForSQL($code)."'": "")."
			";
			$res = $DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

			$pullMessage = Array();
			while ($row = $res->Fetch())
			{
				if ($row['SITE_ID'] == '**')
				{
					foreach ($arSites as $siteId)
					{
						if (isset($pullMessage[$row['CHANNEL_ID']][$siteId][$row['CODE']]))
							$pullMessage[$row['CHANNEL_ID']][$siteId][$row['CODE']] += intval($row['CNT']);
						else
							$pullMessage[$row['CHANNEL_ID']][$siteId][$row['CODE']] = intval($row['CNT']);
					}
				}
				else
				{
					if (isset($pullMessage[$row['CHANNEL_ID']][$row['SITE_ID']][$row['CODE']]))
						$pullMessage[$row['CHANNEL_ID']][$row['SITE_ID']][$row['CODE']] += intval($row['CNT']);
					else
						$pullMessage[$row['CHANNEL_ID']][$row['SITE_ID']][$row['CODE']] = intval($row['CNT']);
				}
			}

			foreach ($pullMessage as $channelId => $arMessage)
			{
				CPullStack::AddByChannel($channelId, Array(
					'module_id' => 'main',
					'command'   => 'user_counter',
					'params'    => $arMessage,
				));
			}
		}
	}

	// legacy function
	public static function GetValueByUserID($user_id, $site_id = SITE_ID, $code = "**")
	{
		return self::GetValue($user_id, $code, $site_id);
	}
	public static function GetCodeValuesByUserID($user_id, $site_id = SITE_ID)
	{
		return self::GetValues($user_id, $site_id);
	}
	public static function GetLastDateByUserAndCode($user_id, $site_id = SITE_ID, $code = "**")
	{
		return self::GetLastDate($user_id, $code, $site_id);
	}
}
?>