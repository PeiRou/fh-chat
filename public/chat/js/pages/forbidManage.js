/**
 * Created by zoe on 2018/5/22.
 */
var roomid = 0;
$(function () {
    $('#menu-forbidManage').addClass('active');

    dataTable = $('#dtTable').DataTable({
        searching: false,
        ordering:false,     //禁止排序
        bLengthChange: false,
        processing: true,
        serverSide: true,
        ajax: {
            url :'/chat/datatables/forbid',
            data:{}
        },
        columns: [
            {data:'regex'},                  //违禁词
            {data:'room_name'},              // 平台
            {data:'updated_at'},             // 更新时间
            {data:function(data){                //操作
                    roomid = data.room_id;
                    return "<ul class='control-menu'>" +
                        "<li onclick='updForbidInfo("+data.chat_regex_idx+")'>修改</li>" +
                        "<li onclick='del("+data.chat_regex_idx+",\"delForbidInfo\")'>删除</li>" +
                        "</ul>";
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

//修改违禁词
function updForbidInfo(id) {
    jc = $.confirm({
        theme: 'material',
        title: '修改违禁词',
        closeIcon:true,
        boxWidth:'20%',
        content: 'url:/chat/modal/editForbidInfo/'+id+'&'+roomid,
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

//新增违禁词
function addForbid() {
    jc = $.confirm({
        theme: 'material',
        title: '添加违禁词',
        closeIcon:true,
        boxWidth:'20%',
        content: 'url:/chat/modal/editForbidInfo/0'+'&'+roomid,
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