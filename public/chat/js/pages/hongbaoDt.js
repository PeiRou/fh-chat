/**
 * Created by zoe on 2018/5/22.
 */
$(function () {
    $('#menu-hongbaoDt').addClass('active');

    dataTable = $('#dtTable').DataTable({
        searching: false,
        ordering:false,     //禁止排序
        bLengthChange: false,
        processing: true,
        serverSide: true,
        ajax: {
            url :'/chat/datatables/hongbaoDt',
            data:{}
        },
        columns: [
            {data:'username'},        //用户名
            {data:'hongbao_idx'},        //红包ID
            {data:'hongbao_dt_orderno'},        //订单号
            {data:'amount'},        //金额
            {data:'getdatetime'},        //领取时间
            {data:function (data) {                 //状态
                    switch (parseInt(data.hongbao_dt_status)){
                        case 1:
                            var txt = "补发中";
                            clsName = 6;      //绿色
                            break;
                        case 2:
                            var txt = "成功";
                            clsName = 1;      //绿色
                            break;
                        case 3:
                            var txt = "失败";
                            clsName = 3;      //红色
                            break;
                    }
                    return '<span class="status-'+clsName+'">'+txt+'</span>';
                }},
            {data:function(){                //操作
                    return 0
                }},
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