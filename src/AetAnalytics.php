<?php
/**
 * AetAnalytics
 *
 * @link https://github.com/exizt/mw-ext-AetAnalytics
 * @author exizt
 * @license GPL-2.0-or-later
 */

class AetAnalytics {
	// 설정값을 갖게 되는 멤버 변수
	private static $config = null;
	// 이용 가능한지 여부 (isAvailable 메소드에서 체크함)
	private static $_isAvailable = true;

	/**
	 * 'BeforePageDisplay' 후킹.
	 *
	 * 
	 * 
	 * @param Article $article
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 * @see https://github.com/wikimedia/mediawiki/blob/master/includes/Hook/BeforePageDisplayHook.php
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		# 최소 유효성 체크
		if( !self::isValid() ){
			return;
		}

		# 설정값 조회
		$config = self::getConfiguration();

		$result = self::getResultHTML( $config, $skin->getContext() );
		if($result){
			$out->addHeadItem('gtag-insert', $result);
		}
	}

	private static function getResultHTML( $config, $context ){
		// 유효성 체크
		if( !self::isAvailable( $config, $context ) ){
			return false;
		}

		return self::makeGoogleAnalyticsHTML( $config['ga_tag_id'] );
	}


	private static function makeGoogleAnalyticsHTML( $tagId ): string{
		if(! $tagId ){
			return '';
		}
		$html = <<<EOT
<script async src="https://www.googletagmanager.com/gtag/js?id={$tagId}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', '{$tagId}');
</script>
EOT;
		return $html;
	}

	/**
	 * AdSense의 ID가 제대로된 입력값인지 확인.
	 */
	private static function isValidTagId( $tagId ){
		if( ! is_string($tagId) || strlen($tagId) < 5 ) {
			return false;
		}

		if( preg_match( '/^(UA-[0-9]+-[0-9]+|G-[0-9A-Z]+)$/', $tagId ) ){
			return true;
		}
		return false;
	}

	/**
	 * 최소 조건 체크.
	 * 
	 * 확장 기능이 동작할 수 있는지에 대한 최소 조건 체크. 성능상 부담이 없도록 구성.
	 */
	private static function isValid(){
		global $wgAetAnalytics;

		# 기존의 체크에서 false 가 되었던 것이 있다면, 바로 false 리턴.
		if( !self::$_isAvailable ){
			return false;
		}

		# 설정되어 있지 않음
		if ( ! isset($wgAetAnalytics) ){
			self::setDisabled();
			return false;
		}

		# 'tag_id'가 유효함
		$tagId = $wgAetAnalytics['ga_tag_id'] ?? '';
		if ( self::isValidTagId( $tagId ) ){
			return true;
		}

		self::setDisabled();
		return false;
	}

	/**
	 * '사용 안 함'을 설정.
	 */
	private static function setDisabled(){
		self::$_isAvailable = false;
	}

	/**
	 * 조건 체크
	 */
	private static function isAvailable( $config, $context ){
		
		# 기존의 체크에서 false 가 되었던 것이 있다면, 바로 false 리턴.
		if( !self::$_isAvailable ){
			return false;
		}

		# 익명 사용자만 해당하는 옵션이 있을 경우.
		if ( $context->getUser()->isRegistered() && $config['anon_only'] ) {
			self::setDisabled();
			return false;
		}

		# 특정 아이피에서는 이용하지 않는다.
		if ( ! empty($config['exclude_ip_list']) ){
			$remoteAddr = $_SERVER["REMOTE_ADDR"] ?? '';
			if( in_array($remoteAddr, $config['exclude_ip_list']) ){
				self::setDisabled();
				return false;
			}
		}

		# self::debugLog("isAvailable");
		# self::debugLog($ns);

		// $titleObj = $context->getTitle();

		// 메인 이름공간의 페이지에서만 나오도록 함. 특수문서 등에서 나타나지 않도록.
		//if( $titleObj->getNamespace() != NS_MAIN ){
		//	self::setDisabled();
		//	return false;
		//}

		return true;
	}

	/**
	 * 설정을 로드함.
	 */
	private static function getConfiguration(){
		# 한 번 로드했다면, 그 후에는 로드하지 않도록 처리.
		if( ! is_null(self::$config) ){
			return self::$config;
		}
		self::debugLog('::getConfiguration');

		global $wgAetAnalytics;

		/*
		* 설정 기본값
		* 
		* ga_tag_id : 구글 애널리틱스 id 값. (예: UA-XXX 또는 G-XXX)
		* anon_only : '비회원'만 애드센스 노출하기.
		* exclude_ip_list : 애드센스를 보여주지 않을 IP 목록.
		*/
		$config = [
			'ga_tag_id' => '',
			'anon_only' => false,
			'exclude_ip_list' => array(),
			'debug' => false
		];
		
		# 설정값 병합
		if (isset($wgAetAnalytics)){
			foreach ($wgAetAnalytics as $key => $value) {
				if( array_key_exists($key, $config) ) {
					if( gettype($config[$key]) == gettype($value) ){
						$config[$key] = $value;
					}
				}
			}
		}

		self::$config = $config;
		return $config;
	}

	/**
	 * 로깅 관련
	 */
	private static function debugLog($msg){
		global $wgDebugToolbar, $wgAetAnalytics;

		# 디버그툴바 사용중일 때만 허용.
		$useDebugToolbar = $wgDebugToolbar ?? false;
		if( !$useDebugToolbar ){
			return false;
		}

		// 로깅
		$isDebug = $wgAetAnalytics['debug'] ?? false;
		if($isDebug){
			if(is_string($msg)){
				wfDebugLog('AetAnalytics', $msg);
			} else if(is_object($msg) || is_array($msg)){
				wfDebugLog('AetAnalytics', json_encode($msg));
			} else {
				wfDebugLog('AetAnalytics', json_encode($msg));
			}
		}
		return false;
	}
}
