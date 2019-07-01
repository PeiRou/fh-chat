/**
 * Created by vincent on 2018/1/29.
 */

$(function () {
    $('#menu-userManage').addClass('active');

    dataTable = $('#dtTable').DataTable({
        searching: false,
        bLengthChange: false,
        ordering:false,
        processing: true,
        serverSide: true,
        aLengthMenu: [[50]],
        ajax: {
            url:'/chat/datatables/user',
            data:function (d) {
                d.account = $('#account').val();    //用户名/呢称
                d.role = $('#role').val();          //角色
                d.statusOnline = $('#statusOnline').val();      //在线状态
                d.status = $('#status').val();      //状态
                d.ip = $('#ip').val();              //登陆ip
            }
        },
        columns: [
            {data:function(data){
                    if(data.online==1)
                        return '<span id="ol_'+data.users_id+'" class="on-line-point">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'+data.users_id+'</span>';
                    else
                        return '<span id="ol_'+data.users_id+'" class="off-line-point">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'+data.users_id+'</span>';
                }},
            {data:'username'},
            {data:'nickname'},
            // {data:'login_ip'},
            {data:'levelname'},
            {"render": function ( data, type, row ) {    //状态
                    var txt = '异常';
                    var fontcolor = '&#xe672;';
                    switch (parseInt(row.chat_status)){
                        case 0:
                            txt = '正常';
                            fontcolor = '&#xe652;';
                            clsName = 1;      //绿色
                            break;
                        case 1:
                            txt = '禁言';
                            fontcolor = '&#xe672;';
                            clsName = 3;      //红色
                            break;
                        default:
                            row.chat_status = 4;
                            clsName  = 4;
                            break;
                    }
                    return '<span class="status-'+clsName+'"><i class="iconfont">'+fontcolor+'</i> '+txt+'</span>';
                }},
            {data:'recharge'},
            {data:'bet'},
            {data: function (data) {
                    if(parseInt(data.chat_status)==1){
                        exe = 'on';
                        txt = '恢复';
                    }else{
                        exe = 'un';
                        txt = '禁言';
                    }
                    return "<ul class='control-menu'>" +
                        "<li onclick='updUserInfo("+data.users_id+","+data.level+","+data.unauto+")'>修改</li>" +
                        "<li onclick='unSpeak("+data.users_id+",\""+data.nickname+"\",\""+exe+"\")'>"+txt+"</li>" +
                        "</ul>";
                }}
        ],
        language: {
            "zeroRecords": "暂无数据",
            "info": "当前显示第 _PAGE_ 页，共 _PAGES_ 页",
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
    })
    // .on('draw.dt', function (e, settings, data) {
    //     checkAllOnline();
    // });
    
    $('#btn_search').on('click',function () {
        dataTable.ajax.reload();
    });
    $('#reset').on('click',function () {
        $('#status').val("");
        $('#rechLevel').val("");
        $('#account').val("");
        $('#mobile').val("");
        $('#qq').val("");
        $('#minMoney').val("");
        $('#maxMoney').val("");
        $('#promoter').val("");
        $('#noLoginDays').val("");
        dataTable.ajax.reload();
    });
});

//跑回圈 检查所有用户上线状态
function checkAllOnline() {
    $('.off-line-point').each(function (){
        data = this.id.split('_');
        checkOnlineId(data[1],this.id);
    });
}

//检查用户上线状态
function checkOnlineId(id,oj) {
    $.ajax({
        url:'/status/notice/getOnlineStatus',
        type:'get',
        dataType:'json',
        data:{id:id},
        success:function (data) {
            if(data.status)
                $('#'+oj).attr('class','on-line-point');
        }});
}

//修改用户角色层级
function updUserInfo(id,level,auto_ct) {
    jc = $.confirm({
        theme: 'material',
        title: '修改用户',
        closeIcon:true,
        boxWidth:'20%',
        content: 'url:/chat/modal/editUserLevel/'+id+'&'+level+'&'+auto_ct,

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
function unSpeak(id,nickname,status) {
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
        content: '确定要对【'+ nickname +'】'+txt+'吗？',
        buttons: {
            confirm: {
                text:'确定',
                btnClass: 'btn-red',
                action: function(){
                    $.ajax({
                        url:'/chat/action/unSpeak/'+id+'&'+status,
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