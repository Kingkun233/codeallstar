<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller {
     /*     
            对上传的项目文件和描述信息进行整理， deal_upload方法实现
            将描述信息存入数据库，将压缩包解压到项目存储文件夹projec中，deal_upload方法实现
            之后对解压后的文件夹读取文件中的文件目录并生成一个数组，dirToArray方法实现
            在读取的过程中遇到项目后缀名符合要求的（包含代码）就读取其中的代码数据和路径等相关信息存入数据库think_file dirToArray方法实现
            之后将return回来的数组序列化后与项目相关信息存入think_pro数据库中 deal_upload方法实现
            当要管理整个项目时（假设时将页面效果展示给用户看） show_pro方法实现（方法所属的html还添加了一段php代码）
            便读取与该用户有关的项目信息，取得其中的文件数组   show_pro方法实现（方法所属的html还添加了一段php代码）
            再通过拼接形成具体路劲，之后数个foreach循环将用户的项目展示给用户看  show_pro方法实现（方法所属的html还添加了一段php代码）
            
            */
    public function index(){
        /*$this->show('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px } a,a:hover,{color:blue;}</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>ThinkPHP</b>！</p><br/>版本 V{$Think.version}</div><script type="text/javascript" src="http://ad.topthink.com/Public/static/client.js"></script><thinkad id="ad_55e75dfae343f5a1"></thinkad><script type="text/javascript" src="http://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script>','utf-8');*/
       $this->display();
    }
    
    public function up_pro(){
        //测试用，主要是上传项目压缩文件
    	$this->display();
    }
    
    public function deal_upload(){
        if(IS_POST){
           
            $interface=$_POST['interface'];
            $pro_name=$_POST['pro_name'];
            $temp_path=$_POST['templtatepath'];
            $static_path=$_POST['staticpath'];
            $upload=new \Think\Upload();
            $upload->maxSize= 10000000;
            $upload->exts=array('jpg', 'gif', 'png', 'zip');
             $upload->savePath  ='./'; 
             $info   =   $upload->upload();    
             if(!$info){
              $this->error($upload->getError());   
               }else{
                 $this->showmessage($info); 
                 foreach($info as $file){
                    if($file['ext']=="zip"){
                        $zip = new \ZipArchive();

                        $upload_file="./Uploads".end(explode(".",$file['savepath'])).$file['savename'];
                        echo $upload_file;
                        
                        if ($zip->open($upload_file,\ZipArchive::CREATE) === TRUE) {
                                $dir_name=$this->getUniName();
                                    $path="./project/".$dir_name;
                                if(is_dir($path)){
                                    $dir_name=$this->getUniName();
                                    $path="./project/".$dir_name;
                                    $res=mkdir(iconv("UTF-8", "GBK", $path),0777,true);
                                    if($res){
                                        echo "目录".$path."创建成功";
                                    }else{
                                        echo "目录".$path."创建失败";
                                    }
                                }else{
                                     $res=mkdir(iconv("UTF-8", "GBK", $path),0777,true);
                                    if($res){
                                        echo "目录".$path."创建成功";
                                    }else{
                                        echo "目录".$path."创建失败";
                                    }
                                }
                                
                                

                                $zip->extractTo($path);//假设解压缩到在当前路径下images文件夹的子文件夹php
                                $time=time();
                                
                                
                                $pro_id=str_shuffle($time);

                                $_SESSION['pro_id']=$pro_id;
                                $_SESSION["separator"]="./project";//设置分隔符
                                $insert_data['pro_id']=$pro_id;
                                $insert_data['pro_name']=$pro_name;
                                
                                $insert_data['basic_path']=$dir_name;
                                $insert_data['user_id']=0;//后期进行修改，目前测试用
                                $insert_data['templatePath']=$temp_path;
                                $insert_data['static_path']=$static_path;
                                $insert_data['zip_path']=end(explode(".",$file['savepath'])).$file['savename'];
                               
                                if($pro_result){
                                    echo "数据插入成功";
                                }
                                
                                $data=$this->dirToArray($path);
                                $insert_data['filearray']=serialize($data);
                                $insert_data['create_time']=time();
                                $insert_data['update_time']=$insert_data['create_time'];
                                $PRO=M('Pro');
                                $PRO->create($insert_data);
                                $pro_result=$PRO->add();
                                $this->showmessage($data);
                               
                                
                                
                    }
                 }
            }
        }
    }
    }
    public function show_pro(){
        $PRO=M("Pro");
        $file=$PRO->where("user_id=0")->order('update_time desc')->getField('filearray',true);
        $basic_path=$PRO->where("user_id=0")->order('update_time desc')->getField('basic_path',true);
        $pro_name=$PRO->where("user_id=0")->order('update_time desc')->getField('pro_name',true);
        foreach ($file as $key => $value) {
            $file[$key]=unserialize($value);
             # code...
         }
        $this->assign("file", $file);
        $this->assign("basic_path", $basic_path);
        $this->assign("pro_name", $pro_name);
        $this->display();
    }
    public function show_promain(){
        $this->display();
    }

    public function down_file(){
        
         $PRO=M("Pro");
         $file_path=$PRO->where("user_id=0")->order('update_time desc')->getField('zip_path');
         $this->download($file_path);
        
    }
   public function searchDir($path,&$data){
    if(is_dir($path)){
        $dp=dir($path);
        while($file=$dp->read()){
        if($file!='.'&& $file!='..'){
        $this->searchDir($path.'/'.$file,$data);
        }
        }
        $dp->close();
        }
        if(is_file($path)){
        $data[]=$path;
        }
   }
   public function download($zip_path){
    //$zip_path=$_POST["zip_path"];
    
    if(isset($zip_path)){
        $file_dir="./Uploads";
        $file_name=$file_dir.$zip_path;//下载文件存放目录    
        //检查文件是否存在
        if(!file_exists($file_name)){
            echo "文件找不到";
        }else{
            $file=fopen($file_name, "r");
            $file_size=filesize($file_name);
            Header("Content-type:application/octetoctet-stream");
            Header("Accept-Ranges:bytes");
            Header("Accept-Length:".$file_size);
            //end(explode("/",$zip_path))
            Header("Content-Disposition:attachment;filename=".end(explode("/",$zip_path)));
                //end(explode("/",$zip_path)));
            $content=fread($file,$file_size);
            echo $content;
            fclose($file);

            exit;
        }

    }else{
        echo "非法操作";
    }
    
   }
   public function getDir($dir){
        $data=array();
        $this->searchDir($dir,$data);
        return $data;
}
    public function showmessage($message=null){
        if(empty($message)){
            echo "null";

        }elseif (is_array($message)||is_object($message)) {
            echo "<pre>";
            print_r($message);
            echo "</pre>";
            # code...
        }else{
            echo $message;
        }
    }
    public function getUniName(){
        return md5(uniqid(microtime(true),true));
    }
    
    
    public function dirToArray($dir){
        $result=array();
        $cdir=scandir($dir);
        foreach ($cdir as $key => $value) {
            if(!in_array($value, array(".",".."))){
                if(is_dir($dir.DIRECTORY_SEPARATOR.$value)){
                    $result[$value]=$this->dirToArray($dir.DIRECTORY_SEPARATOR.$value);
                }else{
                    if(in_array(strtolower(end(explode(".",$value))), array("html","js","css","php"))){
                        $separator=$_SESSION["separator"];
                        $file_path=$dir.DIRECTORY_SEPARATOR.$value;
                        $file['file_path']=end(explode($separator,$file_path ));
                        $file['pro_id']=$_SESSION['pro_id'];
                        $file['file_name']=$value;
                        $file_content=file_get_contents($file_path);
                        $file['file_content']=$file_content;
                        $FILE=M('File');
                        $FILE->create($file);
                        $res=$FILE->add();
                        if(!res){
                            echo "wrong";
                        }
                        //echo "<br />".$file_path."<br />";
                        
                        //$name=end(explode("./Uploads", $path));
                         
                    }
                   $result[$value] = $value;
                    
                }

            }
        }
        return $result;
    }

    



}
