<div style="padding: 0 30px 0 0; min-height: 80vh;">
    <table id="sarchUsers" class="ui small table" cellspacing="0" width="100%" style="table-layout:fixed">
        <thead>
        </thead>
    </table>
</div>
<script>
    $('#sarchUsers').parent().parent().parent('.jconfirm-content').css({"overflow":"initial"});
    $(function(){
        $('#sarchUsers').DataTable({
            // searching: true,
            ordering:false,     //禁止排序
            bLengthChange: false,
            processing: true,
            serverSide: true,
            aLengthMenu: [[12]],
            ajax: {
                url :'/chat/datatables/hongbaoBlacklistSearchUsers',
                data:{
                    chat_hongbao_idx:'{{ request()->chat_hongbao_idx ?? 0 }}'
                }
            },
            columns: [
                {data:'username', title:'账号'},              //房间名称
                {data:'nickname', title:'昵称'},
                {data:'control', title:'操作',"searchable": false,width:'15%'},
            ],
            language: {
                "zeroRecords": "暂无数据",
                "info": "",
                "infoEmpty": "没有记录",
                "loadingRecords": "请稍后...",
                "processing":     "读取中...",
                "paginate": {
                    "first":      "首页",
                    "last":       "尾页",
                    "next":       "下一页",
                    "previous":   "上一页"
                },
                "infoFiltered":" - 从 _MAX_ 记录中过滤",
                "search": "过滤记录:"
            }
        });
    })
</script>