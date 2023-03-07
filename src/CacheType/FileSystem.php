<?php
namespace Zeyuanfly\Cache\CacheType;

class FileSystem{

    private $ext = '.php';
    private $cacheDir = '';
    public $cofnig = [];
    public function __construct($config=[]){
           $this->cofnig = $config;
           $this->cacheDir = $config['cacheDir'];
           if(!is_dir($this->cacheDir)){
               mkdir($this->cacheDir,0777,true);
           }
           if(isset($config['ext'])){
               $this->ext = $config['ext'];
           }
    }

    /**
     * @param $key
     * @param $value
     * @param $expire
     * @return false|int
     * 设置文件缓存
     */
    public function set($key,$value,$expire){
        if(!is_array($value)){
            $putValue = ['value'=>$value,'expire'=>$expire+time()];
        }else{
            $putValue['value'] = $value;
            if(!isset($value['expire'])){
                $putValue['expire'] = time()+$expire;
            }
        }

        $folder = $this->cacheDir;
        $cacheName = $this->getName($key,true);
        if($cacheName){
            $targetCacheNamesArr = explode(DIRECTORY_SEPARATOR,$cacheName);
            if(isset($targetCacheNamesArr[count($targetCacheNamesArr)-1])){
                unset($targetCacheNamesArr[count($targetCacheNamesArr)-1]);
                $folder = $this->cacheDir.implode(DIRECTORY_SEPARATOR,$targetCacheNamesArr);
            }
        }

        $files = $this->searchSameAsTheTableOfContentsFile($folder,md5($key));
        if($files){
            $this->delFile($files);
        }

        return file_put_contents($this->cacheDir.DIRECTORY_SEPARATOR.$cacheName.'-'.($expire+time()).$this->ext,"<?php  \n return ".'$returnValue='.var_export($putValue,true).';');
    }

    /**
     * @param $key
     * @return void
     * 获取名称
     */
    private function getName($key,$isAutoGeFolder=false){
        $fileName = md5($key);
        $processName = '';
        if(strpos($key,'/')) {
            $targetName = explode('/', $key);
            if (is_array($targetName)) {
                if (isset($targetName[count($targetName) - 1])) {
                    $processName = $targetName[count($targetName) - 1];
                    unset($targetName[count($targetName) - 1]);
                }
                if($isAutoGeFolder===true&&!is_dir($this->cacheDir.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $targetName))){
                    mkdir($this->cacheDir.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $targetName),0777,true);
                }
                $fileName = implode(DIRECTORY_SEPARATOR, $targetName) . DIRECTORY_SEPARATOR.md5($key);
            }
        }
        return $fileName;
    }

    /**
     * @param $key
     * @return void
     * 获取文件缓存
     */
    public function get($key){
        //文件是否存在
        $cacheName = $this->getName($key);
        $folder = $this->cacheDir;
        $cacheName = $this->getName($key,true);
        if($cacheName){
            $targetCacheNamesArr = explode(DIRECTORY_SEPARATOR,$cacheName);
            if(isset($targetCacheNamesArr[count($targetCacheNamesArr)-1])){
                unset($targetCacheNamesArr[count($targetCacheNamesArr)-1]);
                $folder = $this->cacheDir.implode(DIRECTORY_SEPARATOR,$targetCacheNamesArr);
            }
        }

        $files = $this->searchSameAsTheTableOfContentsFile($folder,md5($key));

        if(is_array($files)&&isset($files[count($files)-1])){
            $targetFile = $files[count($files)-1];
        }else{
            return null;
        }

        // 分析是否过期
        $processFile = explode('-',$targetFile);
        if(isset($processFile['1'])){
            $targetExpireArr = explode('.php',$processFile[1]);
            if(isset($targetExpireArr[0])){
                $targetExpire = $targetExpireArr[0];
            }

            if($targetExpire==='extended'){
                $value = include ($targetFile);

                if(isset($value['value'])){
                    return $value['value'];
                }
            }elseif($targetExpire>=time()){
                $value = include ($targetFile);

                if(isset($value['value'])){
                    return $value['value'];
                }
            }else{
                return null;
            }
        }
    }

    /**
     * @param $key
     * @return true
     * 删除缓存
     */
    public function remove($key){
        $cacheName = $this->getName($key);
        $folder = $this->cacheDir;
        $cacheName = $this->getName($key,true);
        if($cacheName){
            $targetCacheNamesArr = explode(DIRECTORY_SEPARATOR,$cacheName);
            if(isset($targetCacheNamesArr[count($targetCacheNamesArr)-1])){
                unset($targetCacheNamesArr[count($targetCacheNamesArr)-1]);
                $folder = $this->cacheDir.implode(DIRECTORY_SEPARATOR,$targetCacheNamesArr);
            }
        }

        $files = $this->searchSameAsTheTableOfContentsFile($folder,md5($key));
        if($files){
            $this->delFile($files);
        }
        return true;
    }

    /**
     * @param $path
     * @param $search
     * @return mixed
     * 同目录下文件查找
     */
    protected function searchSameAsTheTableOfContentsFile($path,$search) {
        global $result;
        $files = scandir($path);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $baseName  = basename($file);

                $processFileName = explode('-',$baseName);
                if(isset($processFileName[0])&&$processFileName[0]==$search)
                {
                    $result[] = $path.DIRECTORY_SEPARATOR.$baseName;
                }
            }
        }
        return $result;
    }

    /**
     * @param $file
     * @return void
     * 删除文件
     */
    public function delFile($file){
        if(is_array($file)){
            foreach ($file as $fileVal){
                if (is_file($fileVal))
                unlink($fileVal);
            }
        }else{
            unlink($file);
        }
    }

}