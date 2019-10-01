/**
 * Created by zoe on 2018/5/22.
 */
$(function () {
    $('#menu-roomManage').addClass('active');

    var lottery,roomType;
    $.ajax({
        url :'/chat/modal/getLottery',
        type: 'GET',
        async: false,
        success: function(result) {
            lottery = jQuery.parseJSON(result);
        }
    });
    $.ajax({
        url :'/chat/modal/getRoomType',
        type: 'GET',
        async: false,
        success: function(result) {
            roomType = jQuery.parseJSON(result);
        }
    });
    dataTable = $('#dtTable')
        .on('xhr.dt', function (e, settings, json, xhr) {
            //如果是多房间，就不显示房间的人数
            if(is_rooms==0){
                var column = dataTable.column(2);
                column.visible(false);
            }
        })
        .DataTable({
        searching: false,
        ordering:false,     //禁止排序
        bLengthChange: false,
        processing: true,
        serverSide: true,
        ajax: {
            url :'/chat/datatables/room',
        },
        columns: [
            {data:'room_name'},              //房间名称
            {data:function(data){            //房间类型
                return '<font color="blue">'+roomType[data.roomtype]+'</font>';
                }},
            {data:function(data){                //在线人数
                var sa = data.chat_sas==""?0:(data.chat_sas.split(",")).length;
                return " "+data.online+" / "+data.countUsers+" / "+sa+" ";
                }},
            {data:function(data){              //是否禁言 is_speaking
                if(data.is_speaking=="1") {
                    txt = '正常';
                    fontcolor = '&#xe652;';
                    clsName = 1;      //绿色
                }else {
                    txt = '禁言';
                    fontcolor = '&#xe672;';
                    clsName = 3;      //红色
                }
                return '<span class="status-'+clsName+'"><i class="iconfont">'+fontcolor+'</i> '+txt+'</span>';
                }},
            {data:function(data){              //是否开启快速加入 is_auto
                if(data.is_auto=="1") {
                    txt = '开启';
                    fontcolor = '&#xe652;';
                    clsName = 1;      //绿色
                }else {
                    txt = '关闭';
                    fontcolor = '&#xe672;';
                    clsName = 3;      //红色
                }
                return '<span class="status-'+clsName+'"><i class="iconfont">'+fontcolor+'</i> '+txt+'</span>';
                }},
            {data:function(data){              //是否关闭
                if(data.is_open=="1") {
                    txt = '开启';
                    fontcolor = '&#xe652;';
                    clsName = 1;      //绿色
                }else {
                    txt = '关闭';
                    fontcolor = '&#xe672;';
                    clsName = 3;      //红色
                }
                return '<span class="status-'+clsName+' pointer" onclick="is_open('+data.room_id+','+data.is_open+')"><i class="iconfont">'+fontcolor+'</i> '+txt+'</span>';
                }},
            {data:function(data){              //发言条件
                return '充值量不少于'+data.recharge +';打码量不少于'+data.bet;
                }},
            {data:function (data) {              //计划推送游戏
                var games = data.planSendGame.split(",");
                var repTxt = [];
                if(data.planSendGame!=""){
                    $.each(games, function (index, value) {
                        repTxt.push(lottery[value]);
                    });
                }
                return repTxt.join(',');
                }},
            {data:function (data) {              //修改时间
                return '<img src="'+data.head_img+'" alt="" style="max-height: 50px; max-width: 50px">';
                }},
            {data:function (data) {              //修改时间
                return data.updated_at;
                }},
            {data: function (data) {
                    is_rooms = data.is_rooms;
                    if(parseInt(data.is_speaking)==1){
                        exe = 'un';
                        txt = '禁言';
                    }else{
                        exe = 'on';
                        txt = '恢复';
                    }
                    if(parseInt(data.isTestSpeak)==1){
                        testExe = 'un';
                        testTxt = '关闭测试帐号自动发言';
                    }else{
                        testExe = 'on';
                        testTxt = '开放测试帐号自动发言';
                    }
                    var invUser = "<li onclick='invUser("+data.room_id+")'>管理用户</li>";      //管理用户
                    var invAuto = '';       //快速加入
                    var invSa = '';         //管理员设置
                    if(data.is_rooms==1){
                        if(parseInt(data.is_auto)==1){
                            autoexe = 'un';
                            autotxt = '关闭快速加入';
                        }else{
                            autoexe = 'on';
                            autotxt = '开启快速加入';
                        }
                        invAuto = "<li onclick='openAutoRoom("+data.room_id+",\""+autoexe+"\")'>"+autotxt+"</li>";
                        if(data.room_id !== '1'){
                            invSa = "<li onclick='invAdmin("+data.room_id+")'>管理员设置</li>";
                        }
                    }
                    var delRoom = '';       //预设的房间不可以删除
                    if(data.room_id !== '1' && data.room_id !== '2' && data.room_id !== '3'){
                        delRoom = "<li onclick='delRoom("+data.room_id+")'>删除</li>";
                    }
                    return "<ul class='control-menu'>" +
                        "<li onclick='updRoomInfo("+data.room_id+",\""+data.room_name+"\","+data.roomtype+","+data.recharge+","+data.bet+",\""+data.planSendGame+"\")'>修改</li>" +
                        "<li onclick='unSpeakRoom("+data.room_id+",\""+exe+"\")'>"+txt+"</li>" +
                        invAuto + invUser + invSa +
                        delRoom +
                        "<li onclick='openTestAccount("+data.room_id+",\""+testExe+"\")'>"+testTxt+"</li>" +
                        "</ul>";
                }}
        ],
        language: {
            "zeroRecords": "暂无数据",
            "info": "当前显示第 _START_ 到 _END_ 笔数，总 _TOTAL_ 笔数，当前显示第 _PAGE_ 页，共 _PAGES_ 页",
            "infoEmpty": "没有记录",
            "loadingRecords": "请稍后...",
            "processing":     "读取中...",
            "paginate": {
                "first":      "首页",
                "last":       "尾页",
                "next":       "下一页",
                "previous":   "上一页"
            }
        }
    });
});

//修改房间发言条件
function updRoomInfo(id,name,type,rech,bet,games) {
    jc = $.confirm({
        theme: 'material',
        title: '修改房间信息',
        closeIcon:true,
        boxWidth:'60%',
        content: 'url:/chat/modal/editRoomLimit/'+id+'&'+name+'&'+type+'&'+rech+'&'+bet+'&'+games,
        buttons: {
            confirm: {
                text: '确定提交',
                btnClass: 'btn-blue',
                action: function () {
                    var form = this.$content.find('#updUserForm').data('formValidation').validate().isValid();
                    if(!form){
                        return false;
                    }
                    return false;
                }
            },
            cancel: {
                text:'关闭'
            }
        },
        contentLoaded: function(data, status, xhr){
            $('.jconfirm-content').css('overflow','hidden');
            if(xhr == 'Forbidden')
            {
                this.setContent('<div class="modal-error"><span class="error403">403</span><br><span>您无权进行此操作</span></div>');
                $('.jconfirm-buttons').hide();
            }
        }
    });
}

//执行禁言
function unSpeakRoom(id,status) {
    if(status=="un"){
        txt = '禁言';
    }else{
        txt = '恢复发言';
    }
    jc = $.confirm({
        title: '提示',
        theme: 'material',
        type: 'red',
        boxWidth:'25%',
        content: '确定要对房间进行'+txt+'吗？',
        buttons: {
            confirm: {
                text:'确定',
                btnClass: 'btn-red',
                action: function(){
                    $.ajax({
                        url:'/chat/action/unSpeakRoom/'+id+'&'+status,
                        type:'post',
                        dataType:'json',
                        success:function (data) {
                            if(data.status == true){
                                $('#dtTable').DataTable().ajax.reload(null,false)
                            }
                        },
                        error:function (e) {
                            if(e.status == 403)
                            {
                                Calert('您没有此项权限！无法继续！','red')
                            }
                        }
                    });
                }
            },
            cancel:{
                text:'取消'
            }
        }
    });
}

//执行禁言
function openAutoRoom(id,status) {
    if(status=="on"){
        txt = '开启';
    }else{
        txt = '关闭';
    }
    jc = $.confirm({
        title: '提示',
        theme: 'material',
        type: 'red',
        boxWidth:'25%',
        content: '确定要对房间'+txt+'快速加入吗？',
        buttons: {
            confirm: {
                text:'确定',
                btnClass: 'btn-red',
                action: function(){
                    $.ajax({
                        url:'/chat/action/openAutoRoom/'+id+'&'+status,
                        type:'post',
                        dataType:'json',
                        success:function (data) {
                            if(data.status == true){
                                $('#dtTable').DataTable().ajax.reload(null,false)
                            }
                        },
                        error:function (e) {
                            if(e.status == 403)
                            {
                                Calert('您没有此项权限！无法继续！','red')
                            }
                        }
                    });
                }
            },
            cancel:{
                text:'取消'
            }
        }
    });
}

//执行禁言
function openTestAccount(id,status) {
    if(status=="on"){
        txt = '开放';
    }else{
        txt = '关闭';
    }
    jc = $.confirm({
        title: '提示',
        theme: 'material',
        type: 'red',
        boxWidth:'25%',
        content: '确定要对房间'+txt+'测试帐号聊天吗？',
        buttons: {
            confirm: {
                text:'确定',
                btnClass: 'btn-red',
                action: function(){
                    $.ajax({
                        url:'/chat/action/onTestSpeakRoom/'+id+'&'+status,
                        type:'post',
                        dataType:'json',
                        success:function (data) {
                            if(data.status == true){
                                $('#dtTable').DataTable().ajax.reload(null,false)
                            }
                        },
                        error:function (e) {
                            if(e.status == 403)
                            {
                                Calert('您没有此项权限！无法继续！','red')
                            }
                        }
                    });
                }
            },
            cancel:{
                text:'取消'
            }
        }
    });
}

//新增房间信息
function addRoom() {
    jc = $.confirm({
        theme: 'material',
        title: '添加房间',
        closeIcon:true,
        boxWidth:'60%',
        content: 'url:/chat/modal/editRoomLimit/0',
        buttons: {
            confirm: {
                text: '提交',
                btnClass: 'btn-blue',
                action: function () {
                    var form = this.$content.find('#updUserForm').data('formValidation').validate().isValid();
                    if(!form){
                        return false;
                    }
                    return false;
                }
            },
            cancel: {
                text:'关闭'
            }
        },
        contentLoaded: function(data, status, xhr){
            $('.jconfirm-content').css('overflow','hidden');
            if(xhr == 'Forbidden')
            {
                this.setContent('<div class="modal-error"><span class="error403">403</span><br><span>您无权进行此操作</span></div>');
                $('.jconfirm-buttons').hide();
            }
        }
    });
}
//管理用户
function invUser(id)
{
    jc = $.confirm({
        theme: 'material',
        title: '管理用户',
        closeIcon:true,
        boxWidth:'60%',
        content: 'url:/chat/modal/editRoomUsers/'+id,
        buttons: {
            add: {
                text:'添加',
                btnClass: 'btn-blue',
                action: function () {
                    jcadd = $.confirm({
                        theme: 'material',
                        title: '搜索用户',
                        closeIcon:true,
                        boxWidth:'40%',
                        content: 'url:/chat/modal/editRoomSearchUsers/'+id,
                        buttons: {
                            cancel: {
                                text:'关闭'
                            },
                        },
                    });
                    return false;
                }
            },
            cancel: {
                text:'关闭'
            },
        },
    });
}
//管理设置
function invAdmin(id)
{
    jc = $.confirm({
        theme: 'material',
        title: '管理设置',
        closeIcon:true,
        boxWidth:'60%',
        content: 'url:/chat/modal/editRoomAdmins/'+id,
        buttons: {
            add: {
                text:'添加',
                btnClass: 'btn-blue',
                action: function () {
                    jcadd = $.confirm({
                        theme: 'material',
                        title: '搜索管理',
                        closeIcon:true,
                        boxWidth:'40%',
                        content: 'url:/chat/modal/editRoomSearchAdmins/'+id,
                        buttons: {
                            cancel: {
                                text:'关闭'
                            },
                        },
                    });
                    return false;
                }
            },
            cancel: {
                text:'关闭'
            },
        },
    });
}
//进入房间
function addthis(id, user_id)
{
    $.ajax({
        url:'/chat/action/addRoomUser',
        type:'post',
        data:{
            user_id:user_id,
            id:id
        },
        dataType:'json',
        success:function (data) {
            if(data.status == true){
                $('#users').DataTable().ajax.reload()
                $('#sarchUsers').DataTable().ajax.reload()
            }else{
                $.alert({
                    icon: 'warning sign icon',
                    type: 'red',
                    title: '提示',
                    content: data.msg,
                    boxWidth: '20%',
                    buttons: {
                        ok: {
                            text:'确定',
                        }
                    }
                });
            }
        },
        error:function (e) {
            if(e.status == 403)
            {
                Calert('您没有此项权限！无法继续！','red')
            }
        }
    });
}
//添加管理
function addthisAdmin(roomId, user_id)
{
    $.ajax({
        url:'/chat/action/addRoomAdmin',
        type:'post',
        data:{
            user_id:user_id,
            roomId:roomId
        },
        dataType:'json',
        success:function (data) {
            if(data.status == true){
                $('#admins').DataTable().ajax.reload()
                $('#sarchAdmins').DataTable().ajax.reload()
                $('#dtTable').DataTable().ajax.reload(null,false)
            }else{
                $.alert({
                    icon: 'warning sign icon',
                    type: 'red',
                    title: '提示',
                    content: data.msg,
                    boxWidth: '20%',
                    buttons: {
                        ok: {
                            text:'确定',
                        }
                    }
                });
            }
        },
        error:function (e) {
            if(e.status == 403)
            {
                Calert('您没有此项权限！无法继续！','red')
            }
        }
    });
}
//是否开关
function is_open(roomId, is_open){
    var data = {
        id:roomId,
        is_open:is_open
    };
    var lading = layer.load(1, {
        shade: [0.1,'#fff'] //0.1透明度的白色背景
    });
    $.ajax({
        url:'/chat/action/updRoomInfo',
        type:'post',
        data:data,
        dataType:'json',
        timeout:'5000',
        success:function(e){
            layer.close(lading);
            if(e.status == true)
                $('#dtTable').DataTable().ajax.reload(null,false)

        },
        error:function(e){
            layer.close(lading);
        },
    })
}
//踢出房间
function deleteUser(roomId, user_id){
    var data = {
        roomId:roomId,
        user_id:user_id
    };
    var lading = layer.load(1, {
        shade: [0.1,'#fff'] //0.1透明度的白色背景
    });
    $.ajax({
        url:'/chat/action/deleteUser',
        type:'post',
        data:data,
        dataType:'json',
        timeout:'5000',
        success:function(e){
            layer.close(lading);
            if(e.status == true){
                $('#users').DataTable().ajax.reload()
            }else{
                layer.msg(e.msg);
            }

        },
        error:function(e){
            layer.close(lading);
        },
    })
}
function setPushBet(roomId,user_id,is_pushBet) {
    var data = {
        roomId:roomId,
        user_id:user_id,
        is_pushBet:is_pushBet,
    };
    var lading = layer.load(1, {
        shade: [0.1,'#fff'] //0.1透明度的白色背景
    });
    $.ajax({
        url:'/chat/action/setPushBet',
        type:'post',
        data:data,
        dataType:'json',
        timeout:'5000',
        success:function(e){
            layer.close(lading);
            console.log(e);
            if(e.status == true){
                $('#users').DataTable().ajax.reload()
            }else{
                layer.msg(e.msg);
            }

        },
        error:function(e){
            layer.close(lading);
        },
    })
}
//删除管理
function delAdmin(roomId, user_id){
    var data = {
        roomId:roomId,
        user_id:user_id
    };
    var lading = layer.load(1, {
        shade: [0.1,'#fff'] //0.1透明度的白色背景
    });
    $.ajax({
        url:'/chat/action/delAdmin',
        type:'post',
        data:data,
        dataType:'json',
        timeout:'5000',
        success:function(e){
            layer.close(lading);
            if(e.status == true){
                $('#admins').DataTable().ajax.reload()
                $('#dtTable').DataTable().ajax.reload()
            }else{
                layer.msg(e.msg);
            }

        },
        error:function(e){
            layer.close(lading);
        },
    })
}
//删除房间
function delRoom(roomId){
    var data = {
        roomId:roomId,
    };
    var lading = layer.load(1, {
        shade: [0.1,'#fff'] //0.1透明度的白色背景
    });
    $.ajax({
        url:'/chat/action/delRoom',
        type:'post',
        data:data,
        dataType:'json',
        timeout:'5000',
        success:function(e){
            layer.close(lading);
            if(e.status == true){
                $('#dtTable').DataTable().ajax.reload()
            }else{
                layer.msg(e.msg);
            }

        },
        error:function(e){
            layer.close(lading);
        },
    })
}