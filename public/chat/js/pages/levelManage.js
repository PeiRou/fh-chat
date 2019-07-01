/**
 * Created by zoe on 2018/5/22.
 */
var name = "";
var roomid = 0;
$(function () {
    $('#menu-levelManage').addClass('active');

    dataTable = $('#dtTable').DataTable({
        searching: false,
        ordering:false,     //禁止排序
        bLengthChange: false,
        processing: true,
        serverSide: true,
        ajax: {
            url :'/chat/datatables/level',
            data:{}
        },
        columns: [
            {data:'levelname'},                 // 层级名
            {data:'recharge_min'},              // 充值量
            {data:'bet_min'},                   // 打码量
            {data:'created_at'},                // 新增日期
            {data:'updated_at'},                // 修改日期
            {data:function (data) {             //操作
                    var delEnabel = '';
                    if(data.id=="1")
                        delEnabel = "class='disabled'";
                    else
                        delEnabel = "onclick='updNoteInfo("+data.id+",\""+data.levelname+"\")'";
                    return "<ul class='control-menu'>" +
                        "<li "+delEnabel+"' >修改</li>" +
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

//修改层级
function updNoteInfo(id,name) {
    jc = $.confirm({
        theme: 'material',
        title: '修改层级信息',
        closeIcon:true,
        boxWidth:'20%',
        content: 'url:/chat/modal/editLevelInfo/'+id+'&'+name,
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