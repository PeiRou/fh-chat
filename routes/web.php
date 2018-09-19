<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/','Chat\ChatViewController@AdminLogin');

//获取验证码
Route::group(['prefix' => 'web','namespace'=>'Home'],function (){
    Route::get('getCaptcha','CaptchaController@getCaptcha');
});

//ajax
Route::post('/chat/action/updUserInfo','Chat\ChatAccountController@updUserInfo');               //修改聊天室用户信息
Route::post('/chat/action/unSpeak/{id}','Chat\ChatAccountController@unSpeak');                  //禁言聊天室用户
Route::post('/chat/action/updRoleInfo','Chat\ChatSettingController@updRoleInfo');               //修改角色管理
Route::post('/chat/action/delRoleInfo/{id}','Chat\ChatSettingController@delRoleInfo');          //删除角色管理
Route::post('/chat/action/updRoomInfo','Chat\ChatSettingController@updRoomInfo');               //修改房间信息
Route::post('/chat/action/unSpeakRoom/{id}','Chat\ChatSettingController@unSpeakRoom');          //禁言房间
Route::post('/chat/action/updNoteInfo','Chat\ChatSettingController@updNoteInfo');               //修改聊天室公告
Route::post('/chat/action/delNoteInfo/{id}','Chat\ChatSettingController@delNoteInfo');          //删除聊天室公告
Route::post('/chat/action/updAdminInfo','Chat\ChatSettingController@updAdminInfo');             //修改聊天室管理员
Route::post('/chat/action/delAdminInfo/{id}','Chat\ChatSettingController@delAdminInfo');        //删除聊天室管理员
Route::post('/chat/action/updForbidInfo','Chat\ChatSettingController@updForbidInfo');           //修改聊天室违禁词
Route::post('/chat/action/delForbidInfo/{id}','Chat\ChatSettingController@delForbidInfo');      //修改聊天室违禁词
Route::post('/chat/action/addHongbao','Chat\ChatSettingController@addHongbao');                 //发红包
Route::post('/chat/action/reHongbao/{id}','Chat\ChatSettingController@reHongbao');              //重发红包
Route::post('/chat/action/closeHongbao/{id}','Chat\ChatSettingController@closeHongbao');        //关闭红包
Route::post('/chat/action/openHongbao/{id}','Chat\ChatSettingController@openHongbao');          //开启红包
Route::post('/chat/action/updBaseInfo','Chat\ChatSettingController@updBaseInfo');               //修改平台配置
Route::post('/chat/action/sendPlan','Chat\ChatSettingController@sendPlan');                     //手动发送计画任务
Route::post('/chat/action/updLevelInfo','Chat\ChatSettingController@updLevelInfo');               //修改层级信息

//modal
Route::get('/chat/modal/editUserLevel/{id}','Chat\Ajax\ModalController@editUserLevel');         //显示修改聊天室用户信息-弹窗表单
Route::get('/chat/modal/editRoleInfo/{id}','Chat\Ajax\ModalController@editRoleInfo');           //显示修改用户角色层级-弹窗表单
Route::get('chat/modal/editRoomLimit/{id}','Chat\Ajax\ModalController@editRoomLimit');          //显示修改房间信息-弹窗表单
Route::get('chat/modal/editNoteInfo/{id}','Chat\Ajax\ModalController@editNoteInfo');            //显示修改聊天室公告-弹窗表单
Route::get('chat/modal/editLevelInfo/{id}','Chat\Ajax\ModalController@editLevelInfo');            //显示修改层级信息-弹窗表单
Route::get('chat/modal/editAdminInfo/{id}','Chat\Ajax\ModalController@editAdminInfo');          //显示修改聊天室管理员-弹窗表单
Route::get('chat/modal/editForbidInfo/{id}','Chat\Ajax\ModalController@editForbidInfo');        //显示修改违禁词-弹窗表单
Route::get('chat/modal/addHongbao','Chat\Ajax\ModalController@addHongbao');                //显示发红包-弹窗表单
Route::get('chat/modal/manualPlan','Chat\Ajax\ModalController@manualPlan');                //显示手动发送计画任务-弹窗表单

//datatable
Route::get('/chat/datatables/user','Chat\Data\DataController@userManage');          // 会员管理-表格数据
Route::get('/chat/datatables/role','Chat\Data\DataController@roleManage');          // 角色管理-表格数据
Route::get('/chat/datatables/room','Chat\Data\DataController@roomManage');          // 房间管理-表格数据
Route::get('/chat/datatables/note','Chat\Data\DataController@noteManage');          // 公告管理-表格数据
Route::get('/chat/datatables/level','Chat\Data\DataController@levelManage');        // 层级管理-表格数据
Route::get('/chat/datatables/admin','Chat\Data\DataController@adminManage');        // 管理员管理-表格数据
Route::get('/chat/datatables/forbid','Chat\Data\DataController@forbidManage');      // 违禁词管理-表格数据
Route::get('/chat/datatables/hongbao','Chat\Data\DataController@hongbaoManage');    // 红包管理-表格数据
Route::get('/chat/datatables/hongbaoDt','Chat\Data\DataController@hongbaoDt');      // 红包明细-表格数据
Route::get('/chat/datatables/base','Chat\Data\DataController@baseManage');          // 平台配置-表格数据

//error
Route::get('/error/403',function (){
    return view('403');
})->name('error.403');

//聊天室VIEW
Route::get('/chat','Chat\SrcViewController@AdminLogin');                                                         // 管理登录页面
Route::get('/chat/dash','Chat\ChatViewController@Dash');                                                         // 控制台首页
Route::get('/chat/userManage','Chat\ChatViewController@userManage');                                             // 会员管理
Route::get('/chat/roleManage','Chat\ChatViewController@roleManage');                                             // 角色管理
Route::get('/chat/levelManage','Chat\ChatViewController@levelManage');                                           // 层级管理
Route::get('/chat/roomManage','Chat\ChatViewController@roomManage');                                             // 房间管理
Route::get('/chat/noteManage','Chat\ChatViewController@noteManage');                                             // 公告管理
Route::get('/chat/adminManage','Chat\ChatViewController@adminManage');                                           // 管理员管理
Route::get('/chat/forbidManage','Chat\ChatViewController@forbidManage');                                         // 违禁词管理
Route::get('/chat/hongbaoManage','Chat\ChatViewController@hongbaoManage');                                       // 红包管理
Route::get('/chat/hongbaoDt','Chat\ChatViewController@hongbaoDt');                                               // 红包明细
Route::get('/chat/baseManage','Chat\ChatViewController@baseManage');                                             // 平台配置
//聊天室登入
Route::post('/chat/admin/login','Chat\ChatAccountController@login');
//聊天室登出
Route::get('/chat/admin/logout','Chat\ChatAccountController@logout');

//在线人数状态
Route::get('/status/notice/online','Chat\AjaxStatusController@online');
//检查在线状态
Route::get('/status/notice/getOnlineStatus','Chat\AjaxStatusController@getOnlineStatus');
//检查用户详细信息
Route::get('/hisinfo','Chat\AjaxStatusController@getHisInfo');


