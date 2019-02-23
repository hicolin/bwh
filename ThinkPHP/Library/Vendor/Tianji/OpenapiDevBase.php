<?php
class OpenapiDevBase
{
//	protected $appId = 2010343;
//	protected $orgPrivateKey = '-----BEGIN RSA PRIVATE KEY-----
//MIICXQIBAAKBgQCcxLM3799WNFON52+kQR3dg4Fa4jBUOdLAFJid1wa2bGseECl1
//ndn7oDof0T5Hkg4UTFavxcgd0snLP4BxtY5UiIBmDnGAOma50SKo3G0cV0d0tCF+
//yVVDqabQLroqLh25yVlxOcV5zA2IOhNyw8DOLDHEKhMydtp6DegtmcaflQIDAQAB
//AoGBAJoxLkV6faUAWp5cYIaiNYnG6thFWVu+c/fRSjsVX9jV0pYDN4Qj+l9wPTtG
//R4eFtKtqWmkQk8Ahr7FliCRPeulH/NbF/AMNoxs2VRRuIKEe0a6QY7k3p8TVTlbQ
///lMZUGZ7xcx8knZFyd4P7CRiIgcLIdkbm3miEO0ufLjzzA6BAkEAzwxdTRp3VcGM
//pVSZJQ6e74P9dSMEKv2BCnlraHvBsU1PTeZp4klZNP/ewWjfUsrhYD88tKClOyD4
//0SozNZmDjQJBAMHVIh0Z7wpVbR8onZBdffapR82DNupmc+MsAtdI5HZBWVtLmVrT
//N8xZiIRdi1y6eb84I80edo7gcafWNMkYRikCQDuDnFYLY3388oabeKHUQA8s62/+
//LraEw9DU8fDIkfZ6+G616n2nA8NeQRNrJ7ZOptXZl4N8IcKLSbol3S5tAAkCQDQo
//SOSxzMvoDtP6luN49ONBy+t2KnnKldaESkNp/uf/T68sWZjCC1q2oPCVR2HtX8Nf
//tOqGDvsFVDAIiO2v1XkCQQC76TzJcyqqoZ9TepCzR17JsJJiHOIUW8WADilEV9VH
//bheDUyzOfEtMG/tw/ptaazfBs00S1nHNMAI/Lqd/vB0T
//-----END RSA PRIVATE KEY-----';

//	protected $rong360Url = 'https://openapi.rong360.com/gateway';
	protected $_toBeSigned  = null;
	protected $_postData    = null;

	public function sendRequest($bizData, $method,$AppID,$urls,$orgPrivateKey)
	{/*{{{*/
        $params = array(
            'method'        => $method,
            'app_id'	=> $AppID,
            'version'	=> '2.0',
            'sign_type'	=> 'RSA',
            'format'	=> 'json',
            'timestamp'	=> time()
        );
        $params['biz_data'] = json_encode($bizData);

        $this->_toBeSigned = $this->getSignContent($params);
        $params['sign'] = $this->sign($this->_toBeSigned,$orgPrivateKey);


        $resp = $this->_crulPost($params, $urls);
        return $resp;
	}/*}}}*/

    public function sendRequest2($bizData, $method,$AppID,$urls,$orgPrivateKey)
    {/*{{{*/
        $params = array(
            'method'        => $method,
            'app_id'	=> $AppID,
            'version'	=> '1.0',
            'sign_type'	=> 'RSA',
            'format'	=> 'json',
            'timestamp'	=> time()
        );
        $params['biz_data'] = json_encode($bizData);

        $this->_toBeSigned = $this->getSignContent($params);
        $params['sign'] = $this->sign($this->_toBeSigned,$orgPrivateKey);
//        echo '<pre>';
//        print_r($params);exit;
        $resp = $this->_crulPost($params, $urls);
        return $resp;
    }/*}}}*/
    
    protected function getSignContent($params)
    {/*{{{*/
    	ksort($params);
    
    	$i = 0; 
    	$stringToBeSigned = "";
    	foreach ($params as $k => $v) {
    		if ($i == 0) {
    			$stringToBeSigned .= "$k" . "=" . "$v";
    		} else {
    			$stringToBeSigned .= "&" . "$k" . "=" . "$v";
    		}
    		$i++;
    	}
    	unset ($k, $v);
    	return $stringToBeSigned;
    }/*}}}*/
    
    protected function sign($data,$orgPrivateKey)
    {/*{{{*/
//    	$res = $this->orgPrivateKey;
    	openssl_sign($data, $sign, $orgPrivateKey);
    	$sign = base64_encode($sign);
    	return $sign;
    }/*}}}*/
    
    private function _crulPost($postData, $url='')
    {/*{{{*/
    	if(empty($url)){
    		return false;
    	}
    
        try
        {
            $this->_postData = http_build_query($postData);
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $this->_postData);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl, CURLOPT_SSLVERSION, 1);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            $res = curl_exec($curl);

            $errno = curl_errno($curl);
            $curlInfo = curl_getinfo($curl);
            $errInfo = 'curl_err_detail: ' . curl_error($curl);
            $errInfo .= ' curlInfo:'. json_encode($curlInfo);

            $arrRet = json_decode($res, true);

            //统一记录日志
            $logLevel = 'info';
            if(!is_array($arrRet) || $arrRet['error']!=200) {
                $logLevel = 'warning';
            }
            curl_close($curl);
        }catch(Exception $e)
            {
                print_r($e->getMessage());
            }
    
    
    	if($arrRet['errno']==0){
    		return $arrRet;
    	}

    	return $arrRet;
    }/*}}}*/
}
