<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * Video service providers class
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.video.classes
 * @since 1.0
 */
class VideoProviders
{
    private $code;

    const PROVIDER_YOUTUBE = 'youtube';
    const PROVIDER_METACAFE = 'metacafe';
    const PROVIDER_DAILYMOTION = 'dailymotion';
    const PROVIDER_PORNHUB = 'pornhub';
    const PROVIDER_VIMEO = 'vimeo';
    const PROVIDER_XHAMSTER = 'xhamster';

    const PROVIDER_UNDEFINED = 'undefined';

    private static $provArr;

    public function __construct( $code )
    {
        $this->code = $code;

        $this->init();
    }

    private function init()
    {
        if ( !isset(self::$provArr) )
        {
            self::$provArr = array(
                self::PROVIDER_YOUTUBE => '//www.youtube(-nocookie)?.com/',
                self::PROVIDER_METACAFE => '//www.metacafe.com/',
                self::PROVIDER_DAILYMOTION => '//www.dailymotion.com/',
                self::PROVIDER_PORNHUB => '//www.pornhub.com/',
                self::PROVIDER_VIMEO => '//(player\.)?vimeo.com/',
                self::PROVIDER_XHAMSTER => '//xhamster.com/'
            );
        }
    }

    public function detectProvider()
    {
        foreach ( self::$provArr as $name => $url )
        {
            if ( preg_match("~$url~", $this->code) )
            {
                return $name;
            }
        }
        return self::PROVIDER_UNDEFINED;
    }

    public function getProviderThumbUrl( $provider = null )
    {
        if ( !$provider )
        {
            $provider = $this->detectProvider();
        }

        $className = 'VideoProvider' . ucfirst($provider);

        /** @var $class VideoProviderUndefined */
        if ( class_exists($className) )
        {
            $class = new $className;
        }
        else
        {
            return VideoProviders::PROVIDER_UNDEFINED;
        }
        $thumb = $class->getThumbUrl($this->code);

        return $thumb;
    }
}

class VideoProviderYoutube
{
    const clipUidPattern = '\/\/www\.youtube(-nocookie)?\.com\/(v|embed)\/([^?&"]+)[?&"]';
    const thumbUrlPattern = 'http://img.youtube.com/vi/()/default.jpg';

    private static function getUid( $code )
    {
        $pattern = self::clipUidPattern;

        return preg_match("~{$pattern}~", $code, $match) ? $match[3] : null;
    }

    public static function getThumbUrl( $code )
    {
        if ( ($uid = self::getUid($code)) !== null )
        {
            $url = str_replace('()', $uid, self::thumbUrlPattern);

            return strlen($url) ? $url : VideoProviders::PROVIDER_UNDEFINED;
        }

        return VideoProviders::PROVIDER_UNDEFINED;
    }
}

class VideoProviderMetacafe
{
    const clipUidPattern = 'http://www.metacafe.com/embed/([^/]+)/';
    const thumbUrlPattern = 'http://cdn.mcstatic.com/contents/videos_screenshots/(folder)/()/preview.jpg';

    private static function getUid( $code )
    {
        $pattern = self::clipUidPattern;

        return preg_match("~{$pattern}~", $code, $match) ? (int) $match[1] : null;
    }

    public static function getThumbUrl( $code )
    {
        if ( ($uid = self::getUid($code)) !== null )
        {
            $folder = substr($uid, 0, -3) * 1000;
            $url = str_replace(['(folder)', '()'], [$folder, $uid], self::thumbUrlPattern);

            return strlen($url) ? $url : VideoProviders::PROVIDER_UNDEFINED;
        }

        return VideoProviders::PROVIDER_UNDEFINED;
    }
}

class VideoProviderDailymotion
{
    const clipUidPattern = '//www.dailymotion.com/(swf|embed)/video/([^"]+)"';
    const thumbUrlPattern = '//www.dailymotion.com/thumbnail/video/()';

    private static function getUid( $code )
    {
        $pattern = self::clipUidPattern;

        return preg_match("~{$pattern}~", $code, $match) ? $match[2] : null;
    }

    public static function getThumbUrl( $code )
    {
        if ( ($uid = self::getUid($code)) !== null )
        {
            $url = str_replace('()', $uid, self::thumbUrlPattern);

            return strlen($url) ? $url : VideoProviders::PROVIDER_UNDEFINED;
        }

        return VideoProviders::PROVIDER_UNDEFINED;
    }
}

class VideoProviderPornhub
{
    public static function getThumbUrl( $code )
    {
        return VideoProviders::PROVIDER_UNDEFINED;
    }
}

class VideoProviderVimeo
{
    const clipUidPattern = 'https:\/\/vimeo\.com\/([0-9]*)["]|https:\/\/player\.vimeo\.com\/video\/([0-9]*)[\?]';
    const thumbXmlPattern = 'https://vimeo.com/api/v2/video/().xml';

    private static function getUid( $code )
    {
        $pattern = self::clipUidPattern;
        
        preg_match("~{$pattern}~", $code, $match);
        if ( !empty($match[2]) ) return $match[2];
        if ( !empty($match[1]) ) return $match[1];

        return null;
    }

    public static function getThumbUrl( $code )
    {
        if ( ($uid = self::getUid($code)) !== null )
        {
            $xmlUrl = str_replace('()', $uid, self::thumbXmlPattern);

            $ch = curl_init($xmlUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $fileCont = curl_exec($ch);
            curl_close($ch);
            
            if ( strlen($fileCont) )
            {
                $xml = @simplexml_load_string($fileCont);
                $url = (string)$xml->video->thumbnail_small;
               
                return strlen($url) ? $url : VideoProviders::PROVIDER_UNDEFINED;
            }
        }
        
        return VideoProviders::PROVIDER_UNDEFINED;
    }
}

class VideoProviderXhamster
{
    const clipUidPattern = 'embed\/([^\"]+)\"';
    const thumbFeedPattern = 'http://xhamster.com/xembed.php?video=()';
    const searchThumbPattern = 'https\:\/\/thumb-v-cl2.xhcdn.com\/[^\"]+.jpg';

    private static function getUid( $code )
    {
        $pattern = self::clipUidPattern;

        return preg_match("~{$pattern}~", $code, $match) ? $match[1] : null;
    }
    
    public static function getThumbUrl( $code )
    {
        if ( ($uid = self::getUid($code)) !== null )
        {
            $feedUrl = str_replace('()', $uid, self::thumbFeedPattern);

            $fileCont = @file_get_contents($feedUrl);

            if ( strlen($fileCont) )
            {
                $searchThumbPattern = self::searchThumbPattern;
                preg_match("/{$searchThumbPattern}/", $fileCont, $match);

                $url = isset($match[0]) ? urldecode($match[0]) : VideoProviders::PROVIDER_UNDEFINED;
            }

            return !empty($url) ? $url : VideoProviders::PROVIDER_UNDEFINED;
        }

        return VideoProviders::PROVIDER_UNDEFINED;
    }
}

class VideoProviderUndefined
{
    public static function getThumbUrl( $code )
    {
        return VideoProviders::PROVIDER_UNDEFINED;
    }
}
