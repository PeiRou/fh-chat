<div style="padding: 0 30px 0 0">
    <table id="users" class="ui small table" cellspacing="0" width="100%" style="table-layout:fixed">
        <thead>
        </thead>
    </table>
</div>
<script>
    $(function(){
        $('#users').DataTable({
            searching: false,
            ordering:false,     //禁止排序
            bLengthChange: false,
            processing: true,
            serverSide: true,
            ajax: {
                url :'/chat/datatables/roomUsers/{{ request()->id }}',
                data:{}
            },
            columns: [
                {data:'user_name', title:'账号'},              //房间名称
                {data:'control', title:'操作'},
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
    })
</script>