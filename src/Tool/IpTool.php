<?php
namespace Wslim\Tool;

class IpTool
{
    /*****************************************************
     * sina api: 可设置返回格式 js|json
     * https://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=106.11.154.197
     * {"ret":1,"start":"59.37.164.179″,"end":"59.37.165.17″,"country":"\u4e2d\u56fd","province":"\u5e7f\u4e1c","city":"\u6c5f\u95e8″,"district":"","isp":"\u7535\u4fe1″,"type":"","desc":""};
     * 转义后: 
     * {"ret": 1,"start": -1,"end": -1,"country": "中国","province": "浙江","city": "杭州","district": "","isp": "","type": "","desc": ""}
     * 
     * aliyun api:
     * https://ip.taobao.com/service/getIpInfo.php?ip=106.11.154.197
     * {"code":0,"data":{"country":"\u4e2d\u56fd","country_id":"CN","area":"\u534e\u4e1c","area_id":"300000","region":"\u6d59\u6c5f\u7701","region_id":"330000","city":"\u676d\u5dde\u5e02","city_id":"330100","county":"","county_id":"-1","isp":"\u963f\u91cc\u5df4\u5df4","isp_id":"100098","ip":"106.11.154.197"}}
     * 转义后:
     * {"code": 0,"data": {"country": "中国","country_id": "CN","area": "华东","area_id": "300000","region": "浙江省","region_id": "330000","city": "杭州市","city_id": "330100","county": "","county_id": "-1","isp": "阿里巴巴","isp_id": "100098","ip": "106.11.154.197"}}
     * 
     *****************************************************/
    /**
     * get ip info from ip
     * @param  string $ip
     * @return array  {"code": 0,"data": {"country": "中国","country_id": "CN","area": "华东","area_id": "300000","region": "浙江省","region_id": "330000","city": "杭州市","city_id": "330100","county": "","county_id": "-1","isp": "阿里巴巴","isp_id": "100098","ip": "106.11.154.197"}}
     */
    static public function getInfo($ip)
    {
        $locals = [
            '127.0.0.1', 'localhost'
        ];
        foreach ($locals as $v) {
            if (strpos($ip, $v) !== false) {
                return ['code'=>0, 'data' => ['country' => '本机ip']];
            }
        }
        
        $ip   = @file_get_contents("https://ip.taobao.com/service/getIpInfo.php?ip=".$ip);
        $data = json_decode($ip,true);
        
        return $data;
    }
}