<?php
define("DE_ItemEcode",'Shop_De_');//ʶ���ﳵCookieǰ׺,�ǿ�����Ա�벻Ҫ�������!
/**
 * ���ﳵ��
 *
 * @version        $Id: shopcar.class.php 2 20:58 2010��7��7��Z tianya $
 * @package        DedeCMS.Libraries
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
 // ------------------------------------------------------------------------
 /**
 * ��Ա���ﳵ��
 *
 * @package          MemberShops
 * @subpackage       DedeCMS.Libraries
 * @link             http://www.dedecms.com
 */
class MemberShops
{
    var $OrdersId;
    var $productsId;

    function __construct()
    {
        $this->OrdersId = $this->getCookie("OrdersId");
        if(empty($this->OrdersId))
        {
            $this->OrdersId = $this->MakeOrders();
        }
    }

    function MemberShops()
    {
        $this->__construct();
    }

    /**
     *  ����һ��ר�ж������
     *
     * @return    string
     */
    function MakeOrders()
    {
        $this->OrdersId = 'S-P'.time().'RN'.mt_rand(100,999);
        $this->deCrypt($this->saveCookie("OrdersId",$this->OrdersId));
        return $this->OrdersId;
    }

    /**
     *  ���һ����Ʒ��ż���Ϣ
     *
     * @param     string  $id  ���ﳵID
     * @param     string  $value  ֵ
     * @return    void
     */
    function addItem($id, $value)
    {
        $this->productsId = DE_ItemEcode.$id;
        $this->saveCookie($this->productsId,$value);
    }

    /**
     *  ɾȥһ������ŵ���Ʒ
     *
     * @param     string  $id  ���ﳵID
     * @return    void
     */
    function delItem($id)
    {
        $this->productsId = DE_ItemEcode.$id;
        setcookie($this->productsId, "", time()-3600000,"/");
    }

    /**
     *  ��չ��ﳵ��Ʒ
     *
     * @return    string
     */
    function clearItem()
    {
        foreach($_COOKIE as $key => $vals)
        {
            if(preg_match('/'.DE_ItemEcode.'/', $key))
            {
                setcookie($key, "", time()-3600000,"/");
            }
        }
        return 1;
    }

    /**
     *  �õ�������¼
     *
     * @return    array
     */
    function getItems()
    {
        $Products = array();
        foreach($_COOKIE as $key => $vals)
        {
            if(preg_match("#".DE_ItemEcode."#", $key) && preg_match("#[^_0-9a-z]#", $key))
            {
                parse_str($this->deCrypt($vals), $arrays);
                $values = @array_values($arrays);
                if(!empty($values))
                {
                    $arrays['price'] = sprintf("%01.2f", $arrays['price']);
                    if($arrays['buynum'] < 1)
                    {
                        $arrays['buynum'] = 0;
                    }
                    $Products[$key] = $arrays;
                }
            }
        }
        unset($key,$vals,$values,$arrays);
        return $Products;
    }

    /**
     *  �õ�ָ����Ʒ��Ϣ
     *
     * @param     string  $id  ���ﳵID
     * @return    array
     */
    function getOneItem($id)
    {
        $key = DE_ItemEcode.$id;
        if(!isset($_COOKIE[$key]) && empty($_COOKIE[$key]))
        {
            return '';
        }
        $itemValue = $_COOKIE[$key];
        parse_str($this->deCrypt($itemValue), $Products);
        unset($key,$itemValue);
        return $Products;
    }

    /**
     *  ��ù��ﳵ�е���Ʒ��
     *
     * @return    int
     */
    function cartCount()
    {
        $Products = $this->getItems();
        $itemsCount = count($Products);
        $i = 0;
        if($itemsCount > 0)
        {
            foreach($Products as $val)
            {
                $i = $i+$val['buynum'];
            }
        }
        unset($Products,$val,$itemsCount);
        return $i;
    }

    /**
     *  ��ù��ﳵ�е��ܽ��
     *
     * @return    string
     */
    function priceCount()
    {
        $price = 0.00;
        foreach($_COOKIE as $key => $vals)
        {
            if(preg_match("/".DE_ItemEcode."/", $key))
            {
                $Products = $this->getOneItem(str_replace(DE_ItemEcode,"",$key));
                if($Products['buynum'] > 0 && $Products['price'] > 0)
                {
                    $price = $price + ($Products['price']*$Products['buynum']);
                }
            }
        }
        unset($key,$vals,$Products);
        return sprintf("%01.2f", $price);
    }

    //���ܽӿ��ַ�
    function enCrypt($txt)
    {
        return $this->mchStrCode($txt);
    }

    //���ܽӿ��ַ���
    function deCrypt($txt)
    {
        return $this->mchStrCode($txt,'DECODE');
    }
    
    function mchStrCode($string, $operation = 'ENCODE') 
    {
        $key_length = 4;
        $expiry = 0;
        $key = md5($GLOBALS['cfg_cookie_encode']);
        $fixedkey = md5($key);
        $egiskeys = md5(substr($fixedkey, 16, 16));
        $runtokey = $key_length ? ($operation == 'ENCODE' ? substr(md5(microtime(true)), -$key_length) : substr($string, 0, $key_length)) : '';
        $keys = md5(substr($runtokey, 0, 16) . substr($fixedkey, 0, 16) . substr($runtokey, 16) . substr($fixedkey, 16));
        $string = $operation == 'ENCODE' ? sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$egiskeys), 0, 16) . $string : base64_decode(substr($string, $key_length));

        $i = 0; $result = '';
        $string_length = strlen($string);
        for ($i = 0; $i < $string_length; $i++){
            $result .= chr(ord($string{$i}) ^ ord($keys{$i % 32}));
        }
        if($operation == 'ENCODE') {
            return $runtokey . str_replace('=', '', base64_encode($result));
        } else {
            if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$egiskeys), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        }
    }

    //���л�����
    function enCode($array)
    {
        $arrayenc = array();
        foreach($array as $key => $val)
        {
            $arrayenc[] = $key.'='.urlencode($val);
        }
        return implode('&', $arrayenc);
    }

    //�������ܵ�_cookie
    function saveCookie($key,$value)
    {
        if(is_array($value))
        {
            $value = $this->enCrypt($this->enCode($value));
        }
        else
        {
            $value = $this->enCrypt($value);
        }
        setcookie($key,$value,time()+36000,'/');
    }

    //��ý��ܵ�_cookie
    function getCookie($key)
    {
        if(isset($_COOKIE[$key]) && !empty($_COOKIE[$key]))
        {
            return $this->deCrypt($_COOKIE[$key]);
        }
    }
}