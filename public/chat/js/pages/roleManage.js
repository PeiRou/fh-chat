/**
 * Created by zoe on 2018/5/23.
 */
$(function () {
    $('#menu-roleManage').addClass('active');

    dataTable = $('#dtTable').DataTable({
        searching: false,
        ordering:false,     //禁止排序
        bLengthChange: false,
        processing: true,
        serverSide: true,
        ajax: {
            url :'/chat/datatables/role',
            data:{}
        },
        columns: [
            {data:'name'},              //角色名
            {data:function (data) {         //聊天信息效果
                    data.msg = '聊天信息效果';
                    if(data.bg2 =='')
                        var postMsg = "<div style='border-radius: 3px; color: #"+data.font+"; font-size: 12px; padding: 5px;  background-color: #"+data.bg1+";'>"+data.msg+"</div>";
                    else
                        var postMsg = "<div style='border-radius: 3px; color: #"+data.font+"; font-size: 12px; padding: 5px;  background: -webkit-linear-gradient(left, #"+data.bg1+",#"+data.bg2+"); background: -o-linear-gradient(right, #"+data.bg1+",#"+data.bg2+"); background: -moz-linear-gradient(right, #"+data.bg1+",#"+data.bg2+"); background: linear-gradient(to right, #"+data.bg1+",#"+data.bg2+");'>"+data.msg+"</div>";
                    return postMsg;
                }},
            {data:'length'},                //消息最大长度
            {data:function (data){          //权限 permission
                    arrayPer = data.permission.split(',');
                    var allli = "";
                    $.each(arrayPer, function( index, value ) {
                        switch (parseInt(value)){     //1:发言 2:发图 3:踢人 4:禁言
                            case 1:
                                allli = allli + "<button class='ui green basic button mini'>发言</button>";
                                break;
                            case 2:
                                allli = allli + "<button class='ui green basic button mini'>发图</button>";
                                break;
                            case 3:
                                allli = allli + "<button class='ui yellow basic button mini'>踢人</button>";
                                break;
                            case 4:
                                allli = allli + "<button class='ui yellow basic button mini'>禁言</button>";
                                break;
                        }
                    });
                    return "<ul class='control-menu'>" + allli + "</ul>";
                }},
            {data:'description'},           //描述
            {data:function (data) {         //操作
                    var delEnabel = '';
                    if(data.id=="1" || data.id=="2"|| data.id=="4"|| data.id=="7")
                        delEnabel = "class='disabled'";
                    else
                        delEnabel = "onclick='del("+data.id+",\"delRoleInfo\")'";
                    return "<ul class='control-menu'>" +
                        "<li onclick='updRoleInfo("+data.id+")'>修改</li>" +
                        "<li "+delEnabel+"'>删除</li>" +
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
    });
});

//修改用户角色层级
function updRoleInfo(id) {
    jc = $.confirm({
        theme: 'material',
        title: '修改角色',
        closeIcon:true,
        boxWidth:'20%',
        content: 'url:/chat/modal/editRoleInfo/'+id,
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

//新增用户角色层级
function addRole() {
    jc = $.confirm({
        theme: 'material',
        title: '添加角色',
        closeIcon:true,
        boxWidth:'20%',
        content: 'url:/chat/modal/editRoleInfo/'+"0",
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