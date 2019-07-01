/**
 * Created by zoe on 2018/5/22.
 */
$(function () {
    $('#menu-hongbaoDt').addClass('active');

    var today = new Date();
    $('#rangestart').calendar({
        type: 'date',
        endCalendar: $('#rangeend'),
        formatter: {
            date: function (date, settings) {
                if (!date) return '';
                var day = date.getDate();
                var month = date.getMonth() + 1;
                var year = date.getFullYear();
                return year+'-'+month+'-'+day;
            }
        },
        text: {
            days: ['日', '一', '二', '三', '四', '五', '六'],
            months: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
            monthsShort: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
            today: '今天',
            now: '现在',
            am: 'AM',
            pm: 'PM'
        },
        minDate: new Date(today.getFullYear(), today.getMonth(), today.getDate() - 99),
        maxDate: new Date(today.getFullYear(), today.getMonth(), today.getDate())
    });
    $('#rangeend').calendar({
        type: 'date',
        startCalendar: $('#rangestart'),
        formatter: {
            date: function (date, settings) {
                if (!date) return '';
                var day = date.getDate();
                var month = date.getMonth() + 1;
                var year = date.getFullYear();
                return year+'-'+month+'-'+day;
            }
        },
        text: {
            days: ['日', '一', '二', '三', '四', '五', '六'],
            months: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
            monthsShort: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
            today: '今天',
            now: '现在',
            am: 'AM',
            pm: 'PM'
        },
        minDate: new Date(today.getFullYear(), today.getMonth(), today.getDate() - 99),
        maxDate: new Date(today.getFullYear(), today.getMonth(), today.getDate())
    });

    dataTable = $('#dtTable').DataTable({
        searching: false,
        ordering:false,     //禁止排序
        bLengthChange: false,
        processing: true,
        serverSide: true,
        ajax: {
            url :'/chat/datatables/hongbaoDt',
            data:function (d) {
                d.timeStart = $('#timeStart').val();
                d.timeEnd = $('#timeEnd').val();
                d.id = $('#id').val();              //红包id
                d.or_id = $('#or_id').val();              //订单号
                d.account = $('#account').val();              //用户名
                d.status = $('#status').val();      //状态
                d.min_amount = $('#min_amount').val();      //最小金额
                d.max_amount = $('#max_amount').val();      //最大金额
            }
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
                    litxt = "<li class='disabled' >补发</li>";
                    return "<ul class='control-menu'>" + litxt + "</ul>";
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
    $('#btn_search').on('click',function () {
        dataTable.ajax.reload();
    });
    $('#reset').on('click',function () {
        $('#id').val("");
        $('#or_id').val("");              //订单号
        $('#account').val("");              //用户名
        $('#status').val("");
        $('#status').val("");               //状态
        $('#min_amount').val("");           //最小金额
        $('#max_amount').val("");           //最大金额
        dataTable.ajax.reload();
    });
});