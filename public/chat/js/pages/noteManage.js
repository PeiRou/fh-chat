/**
 * Created by zoe on 2018/5/22.
 */
var name = "";
var roomid = 0;
$(function () {
    $('#menu-noteManage').addClass('active');

    var allAooms;
    $.ajax({
        url :'/chat/modal/getAllRooms',
        type: 'GET',
        success: function(result) {
            allAooms = jQuery.parseJSON(result);
        }
    });
    dataTable = $('#dtTable').DataTable({
        searching: false,
        ordering:false,     //禁止排序
        bLengthChange: false,
        processing: true,
        serverSide: true,
        ajax: {
            url :'/chat/datatables/note',
            data:{}
        },
        columns: [
            {data:'content'},              //公告内容
            // {data:'room_name'},              //房间
            {data:function (data) {
                if(data.is_rooms==1){
                    var rooms = data.rooms.split(",");//4,5,6
                    var repTxt = [];
                    if(data.rooms!=""){
                        console.log(allAooms)
                        $.each(rooms, function (index, value) {
                            $.each(allAooms, function (index1, value1) {
                                if(value==value1.room_id)
                                    repTxt.push(value1.room_name);
                            });
                        });
                    }
                    return repTxt.join(',');
                }else{
                    return data.room_name;
                }
                }},              //房间
            {data:'created_at'},            // 添加时间
            {data:'add_account'},           // 添加人
            {data:'updated_at'},            // 修改时间
            {data:'upd_account'},           // 修改人
            {data:function (data) {         //操作
                    roomid = data.room_id;
                    name = data.room_name;
                    return "<ul class='control-menu'>" +
                        "<li onclick='updNoteInfo("+data.chat_note_idx+",\""+data.room_name+"\")'>修改</li>" +
                        "<li onclick='del("+data.chat_note_idx+",\"delNoteInfo\")'>删除</li>" +
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

//修改公告信息
function updNoteInfo(id,name) {
    jc = $.confirm({
        theme: 'material',
        title: '修改聊天室公告信息',
        closeIcon:true,
        boxWidth:'20%',
        content: 'url:/chat/modal/editNoteInfo/'+id+'&'+name,
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

//新增公告信息
function addNote() {
    jc = $.confirm({
        theme: 'material',
        title: '添加聊天室公告',
        closeIcon:true,
        boxWidth:'20%',
        content: 'url:/chat/modal/editNoteInfo/0'+'&'+name+'&'+roomid,
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