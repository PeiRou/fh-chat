<div style="padding: 0 30px 0 0">
    <table id="admins" class="ui small table" cellspacing="0" width="100%" style="table-layout:fixed">
        <thead>
        </thead>
    </table>
</div>
<script>
    var users;
    $(function(){
        users = $('#admins').DataTable({
            searching: true,
            ordering:false,     //禁止排序
            bLengthChange: false,
            processing: true,
            serverSide: true,
            // aLengthMenu: [[5]],
            ajax: {
                url :'/chat/datatables/roomAdmins/{{ request()->id }}',
                data:{}
            },
            columns: [
                {data:'user_name', title:'账号'},              //房间名称
                {data:'control', title:'操作', width:'20%'},
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
                },
                "infoFiltered":" - 从 _MAX_ 记录中过滤",
                "search": "过滤记录:"
            }
        });
    })
</script>