<?php

class Tool
{
    /**
     * 获取当天开始和结束时间 的时间戳
     * Author: jkSen
     * Date  : 2019/3/9 1:03
     * @return array
     */
    static function getTodayStartAndEnd()
    {
        date_default_timezone_set('PRC');
        $year  = date("Y");
        $month = date("m");
        $day   = date("d");
        $start = mktime(00, 00, 00, $month, $day, $year);//当天开始时间戳
        $end   = mktime(23, 59, 59, $month, $day, $year);//当天结束时间戳
        return ['start' => $start, 'end' => $end];
    }

    /**
     * 获取昨天天开始和结束时间 的时间戳
     * Author: jkSen
     * Date  : 2019/3/9 1:07
     * @return array
     */
    static function getYesterdayStartAndEnd()
    {
        date_default_timezone_set('PRC');
        $start = strtotime(date('Y-m-d', strtotime('yesterday')));
        $end   = strtotime(date('Y-m-d')) - 1;
        return ['start' => $start, 'end' => $end];
    }

    /**
     * 获取指定年月日的开始时间戳和结束时间戳(本地时间戳非GMT时间戳)
     * Author: jkSen
     * Date  : 2019/3/9 1:07
     * @param int $year
     * @param int $month
     * @param int $day
     * @return array
     */
    static function getOneDayStartAndEndUnixTimestamp($year = 0, $month = 0, $day = 0)
    {
        date_default_timezone_set('PRC');
        if (empty($year)) {
            $year = date("Y");
        }

        $start_year          = $year;
        $start_year_formated = str_pad(intval($start_year), 4, "0", STR_PAD_RIGHT);
        $end_year            = $start_year + 1;
        $end_year_formated   = str_pad(intval($end_year), 4, "0", STR_PAD_RIGHT);

        if (empty($month)) {
            //只设置了年份
            $start_month_formated = '01';
            $end_month_formated   = '01';
            $start_day_formated   = '01';
            $end_day_formated     = '01';
        } else {

            $month > 12 || $month < 1 ? $month = 1 : $month = $month;
            $start_month          = $month;
            $start_month_formated = sprintf("%02d", intval($start_month));

            if (empty($day)) {
                //只设置了年份和月份
                $end_month = $start_month + 1;

                if ($end_month > 12) {
                    $end_month = 1;
                } else {
                    $end_year_formated = $start_year_formated;
                }
                $end_month_formated = sprintf("%02d", intval($end_month));
                $start_day_formated = '01';
                $end_day_formated   = '01';
            } else {
                //设置了年份月份和日期
                $startTimestamp = strtotime($start_year_formated . '-' . $start_month_formated . '-' . sprintf("%02d", intval($day)) . " 00:00:00");
                $endTimestamp   = $startTimestamp + 24 * 3600 - 1;
                return array('start' => $startTimestamp, 'end' => $endTimestamp);
            }
        }

        $startTimestamp = strtotime($start_year_formated . '-' . $start_month_formated . '-' . $start_day_formated . " 00:00:00");
        $endTimestamp   = strtotime($end_year_formated . '-' . $end_month_formated . '-' . $end_day_formated . " 00:00:00") - 1;
        return ['start' => $startTimestamp, 'end' => $endTimestamp];
    }

    /**
     * 多重条件排序
     * Author: jkSen
     * Date  : 2019/3/9 1:09
     *$sortInfoArray = [
     * ['sortKey'=>'volume','sortOrder'=>SORT_ASC,'sortType'=>SORT_NUMERIC],
     * ['sortKey'=>'edition','sortOrder'=>SORT_DESC,'sortType'=>SORT_NUMERIC],
     * ['sortKey'=>'key','sortOrder'=>SORT_ASC,'sortType'=>SORT_STRING],
     * ];
     * @param $dataArray
     * @param $sortInfoArray
     * @return array
     */
    static function arrayMultisortJk($dataArray, $sortInfoArray)
    {
        if (empty($dataArray)) {
            return $dataArray;
        }
        if (empty($sortInfoArray) || !is_array($sortInfoArray)) {
            return $sortInfoArray;
        }
        $sortRule   = [];
        $useForSort = [];
        foreach ($sortInfoArray as $key => $sortArr) {
            if (!array_key_exists('sortKey', $sortArr)) {
                unset($sortInfoArray[$key]);
                continue;
            }

            $sortRule[$sortArr['sortKey']] = '$useForSort[\'' . $sortArr['sortKey'] . '\']';
            if (array_key_exists('sortOrder', $sortArr)) {
                $sortRule[$sortArr['sortKey']] .= ',' . $sortArr['sortOrder'];
            }
            if (array_key_exists('sortType', $sortArr)) {
                $sortRule[$sortArr['sortKey']] .= ',' . $sortArr['sortType'];
            }
        }
        foreach ($dataArray as $key => $vArr) {
            foreach ($sortInfoArray as $sortArr) {
                $useForSort[$sortArr['sortKey']][$key] = $vArr[$sortArr['sortKey']];
            }
        }
        $evalString = 'array_multisort(' . implode(',', $sortRule) . ',$dataArray);';
        eval($evalString);
        return $dataArray;
    }

    /**
     * 保存微信的图片到本地服务器
     * Author: jkSen
     * Date  : 2019/3/9 1:13
     * @param $url
     * @param string $filename
     * @return bool|string
     */
    static function saveWxImageToLocal($url, $filename = '')
    {
        $pathPre = PHP_OS === 'WINNT' ? '/static/img/' : 'D:/Work/';

        if ($filename == '') {
            $filename = date('YmdHis') . mb_substr(uniqid(), -6) . makeRandStr(3);
        }

        $yeamMonthWeek = date('Ymd');
        $upload_path   = $pathPre . '/' . $yeamMonthWeek;
        if (!creatDirectory($upload_path)) {
            //创建目录失败
            return false;
        }

        $header = array(
            'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:45.0) Gecko/20100101 Firefox/45.0',
            'Accept-Language: zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3',
            'Accept-Encoding: gzip, deflate',);
        $curl   = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        $data = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($code == 200) {//把URL格式的图片转成base64_encode格式的！
            $imgBase64Code = "data:image/jpeg;base64," . base64_encode($data);
        } else {
            //微信资源下载失败
            return false;
        }

        $img_content = $imgBase64Code;//图片内容

        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $img_content, $result)) {
            $type     = $result[2];
            $new_file = $upload_path . "/$filename.{$type}";
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $img_content)))) {
                return '/' . $yeamMonthWeek . '/' . $filename . '.' . $type;
            } else {
                //头像保存失败
                return false;
            }
        }
        return false;
    }

    /**
     * 打印函数
     * Author: jkSen
     * Date  : 2019/3/9 1:14
     * @param $param
     */
    static function pr($param)
    {
        echo '<pre>';
        print_r($param);
        echo '</pre>';
    }

    /**
     * 打印函数并且终止程序
     * Author: jkSen
     * Date  : 2019/3/9 1:15
     * @param $param
     */
    static function pre($param)
    {
        echo '<pre>';
        print_r($param);
        echo '</pre>';
        exit();
    }
}

?>