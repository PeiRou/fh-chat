<div style="padding: 0 30px 0 0">
    <table id="users" class="ui small table" cellspacing="0" width="100%" style="table-layout:fixed">
        <thead>
        </thead>
    </table>
</div>
<script>
    var users;
    $(function(){
        users = $('#users').DataTable({
            searching: true,
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
                {data:function (data) {
                    if(data.is_speaking == 1){
                        return '是';
                    }else if(data.is_speaking == 0){
                        return '否';
                    }
                },title:'是否能说话'},
                {data:function (data) {
                    if(data.is_pushbet == 1){
                        return '是';
                    }else if(data.is_pushbet == 0){
                        return '否';
                    }
                },title:'是否能跟单'},
                {data:'control', title:'操作', width:'20%'},
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
                },
                "infoFiltered":" - 从 _MAX_ 记录中过滤",
                "search": "过滤记录:"
            }
        });
    })
</script>