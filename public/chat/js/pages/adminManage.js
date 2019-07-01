/**
 * Created by zoe on 2018/5/22.
 */
$(function () {
    $('#menu-adminManage').addClass('active');

    dataTable = $('#dtTable').DataTable({
        searching: false,
        ordering:false,     //禁止排序
        bLengthChange: false,
        processing: true,
        serverSide: true,
        ajax: {
            url :'/chat/datatables/admin',
            data:{}
        },
        columns: [
            {data:'account'},              //管理员帐号
            {data:'name'},                 // 名称
            {data:'created_at'},           // 添加时间
            {data:function(data){                //操作
                return data.control;
                    // var delEnabel = '';
                    // if(data.sa_id=="1")
                    //     delEnabel = "disabled";
                    // var google = data.account == 'admin' ? '' : "<li onclick='google("+data.sa_id+")'> Google双重验证</li>";
                    // return "<ul class='control-menu'>" +
                    //     "<li onclick='updAdminInfo("+data.sa_id+",\""+data.account+"\",\""+data.name+"\")'>修改</li>" +
                    //     google +
                    //     "<li class='"+delEnabel+"' onclick='del("+data.sa_id+",\"delAdminInfo\")'>删除</li>" +
                    //     "</ul>";
                }},
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

//修改管理员信息
function updAdminInfo(id,ac,name) {
    jc = $.confirm({
        theme: 'material',
        title: '修改聊天室管理员',
        closeIcon:true,
        boxWidth:'20%',
        content: 'url:/chat/modal/editAdminInfo/'+id+'&'+ac+'&'+name,
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

function google(id) {
    var url = '/chat/modal/googleSubAccount/'+id;
    Cmodal('Google双重验证','22%',url,false,'');
}

//新增管理员信息
function addAdmin() {
    jc = $.confirm({
        theme: 'material',
        title: '添加聊天室管理员',
        closeIcon:true,
        boxWidth:'20%',
        content: 'url:/chat/modal/editAdminInfo/0',
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