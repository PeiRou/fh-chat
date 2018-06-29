/**
 * Created by zoe on 2018/5/22.
 */
$(function () {
    $('#menu-hongbaoManage').addClass('active');

    dataTable = $('#dtTable').DataTable({
        searching: false,
        ordering:false,     //禁止排序
        bLengthChange: false,
        processing: true,
        serverSide: true,
        ajax: {
            url :'/chat/datatables/hongbao',
            data:{}
        },
        columns: [
            {data:'chat_hongbao_idx'},              //红包ID
            {data:'room_name'},                     //房间
            {data:function (data) {                 //状态
                    switch (parseInt(data.hongbao_status)){
                        case 1:
                            var txt = "抢疯中";
                            clsName = 1;      //绿色
                            break;
                        case 2:
                            var txt = "已抢完";
                            clsName = 3;      //红色
                            break;
                        case 3:
                            var txt = "已关闭";
                            clsName = 3;      //红色
                            break;
                    }
                    return '<span class="status-'+clsName+'">'+txt+'</span>';
                }},
            {data:'hongbao_total_amount'},         //总金额
            {data:'hongbao_total_num'},            //红包个数
            {data:'hongbao_remain_amount'},        //剩馀馀额
            {data:function(data){                  //抢红包条件(最近2天)  recharge  bet
                return "充值量不少于"+data.recharge+";打码量不少于"+data.bet;
                }},
            {data:'posttime'},                      //发送时间
            {data:'account'},                       //操作人
            {data:function(){                       //操作
                return 0;
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