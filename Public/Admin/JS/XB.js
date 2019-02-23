$.XB = {
    //成功对话框
    success: function (options) {
        var settings = {
            "message": "恭喜您，操作成功！",
            "fn": function () { }
        };
        if (options) {
            $.extend(settings, options);
        }
        $.messager.alert('成功', settings.message, 'info', settings.fn);
    },
    //错误对话框
    error: function (options) {
        var settings = {
            "message": "很抱歉，操作错误，请刷新或稍后重试！",
            "fn": function () { }
        };
        if (options) {
            $.extend(settings, options);
        }
        $.messager.alert('错误', settings.message, 'error', settings.fn);
    },
    //警示对话框
    warning: function (options) {
        var settings = {
            "message": "温馨提示，请您谨慎操作！",
            "fn": function () { }
        };
        if (options) {
            $.extend(settings, options);
        }
        $.messager.alert('警示', settings.message, 'warning', settings.fn);
    },
    //确认对话框
    //必须传入调用函数
    confirm: function (options) {
        var settings = {
            "message": "你确定进行此项操作吗？点击确定为继续执行，点击取消则终止操作。",
            "fn": function () { }
        };
        if (options) {
            $.extend(settings, options);
        }
        $.messager.confirm('确认对话框', settings.message, function (r) {
            if (r) {
                settings.fn();
            }
        });
    },
    //刷新整个页面，重置了查询条件
    topreload: function () {
        location.reload(true);
    },
    //刷新数据列表，保留查询条件
    reload: function (options) {
        var settings = {
            "id": "#DataList"
        };
        if (options) {
            $.extend(settings, options);
        }
        if ($(settings.id).length > 0) {
            if ($(settings.id).datagrid('options')["idField"] == null) {
                $(settings.id).datagrid('reload');
            }
            else {
                $.XB.reloadtreegrid(settings);
            }
        }
    },
    //刷新树形数据列表，保留查询条件
    reloadtreegrid: function (options) {
        var settings = {
            "id": "#DataList"
        };
        if (options) {
            $.extend(settings, options);
        }
        if ($(settings.id).length>0) {
            $(settings.id).treegrid('reload');
        }
    },
    //回车触发搜索条件提交
    enter: function () {
        $(document).keydown(function (e) {
            var e = e || event,
            keycode = e.which || e.keyCode;
            if (keycode == 13) {
                $.XB.search();
            }
        });
    },
    //获取数据列表中单条记录指定的字段值
    getrowid: function (options) {
        var settings = {
            "id": "ID",
            "datagrid": "#DataList"
        };
        if (options) {
            $.extend(settings, options);
        }
        var checkedItems = $(settings.datagrid).datagrid('getSelected');
        if (checkedItems) {
            return checkedItems[settings.id];
        }
        else {
            return 0;
        }
    },
    //获取数据列表中多条记录指定的字段值
    getallrowid: function (options) {
        var settings = {
            "id": "ID",
            "datagrid": "#DataList"
        };
        if (options) {
            $.extend(settings, options);
        }
        var checkedItems = $(settings.datagrid).datagrid('getChecked');
        var rows = [];
        $.each(checkedItems, function (index, item) {
            rows.push(item[settings.id]);
        });
        if (rows.length == 0) {
            return 0;
        }
        else {
            return rows.join(",");
        }
    },
    //创建DIV
    creatediv: function (options) {
        var settings = {
            "id": "#D1"
        };
        if (options) {
            $.extend(settings, options);
        }
        var window = $(".window");
        var len = window.length;
        if (len > 0) {
            for (var index = 0; index < len; index++) {
                if ($(window[index]).is(":hidden")) {
                    $(window[index]).remove();
                }
            }
        }
        var windowshadow = $(".window-shadow");
        var len = windowshadow.length;
        if (len > 0) {
            for (var index = 0; index < len; index++) {
                if ($(windowshadow[index]).is(":hidden")) {
                    $(windowshadow[index]).remove();
                }
            }
        }
        var windowmask = $(".window-mask");
        var len = windowmask.length;
        if (len > 0) {
            for (var index = 0; index < len; index++) {
                if ($(windowmask[index]).is(":hidden")) {
                    $(windowmask[index]).remove();
                }
            }
        }

        var div = document.createElement("div");
        div.id = settings.id.replace("#", "");
        document.body.appendChild(div);
        $(settings.id).addClass("NoScroll");
    },
    //打开窗口引导方法
    open: function (options) {
        var settings = {
            "datalist": "#DataList",
            "type": "refresh",
            "openmode": '0',
            "rowid": null,
            "rowindex": null,
            "token": null
        };
        if (options) {
            $.extend(settings, options);
        }
        switch (settings.type) {
            case 'refresh':
                $.XB.reload();
                break;
            case 'add':
                if (settings.openmode == 4) {
                    var tab = top.$('#MTabs').tabs('getSelected');
                    var title = tab.panel('options').title;
                    top.AddTag(title + ':' + settings.dialog.title, settings.dialog.url, settings.dialog.iconCls);
                }
                else {
                    $.XB.dialog(settings.dialog);
                }
                break;
            default:
                switch (settings.openmode) {
                    case '0'://有交互窗口
                        var ID = settings.rowid || $.XB.getrowid();
                        if (ID == 0) {
                            $.XB.warning({ "message": "很抱歉，请选择需要操作的数据行！" });
                            return false;
                        }
                        settings.dialog.url = (settings.dialog.url.indexOf('?') > -1) ? settings.dialog.url + "&ID=" + ID : settings.dialog.url + "?ID=" + ID;
                        $.XB.dialog(settings.dialog);
                        break;
                    case '1'://无交互的窗口
                        var ID = settings.rowid || $.XB.getrowid();
                        if (ID == 0) {
                            $.XB.warning({ "message": "很抱歉，请选择需要操作的数据行！" });
                            return false;
                        }
                        settings.dialog.url = (settings.dialog.url.indexOf('?') > -1) ? settings.dialog.url + "&ID=" + ID : settings.dialog.url + "?ID=" + ID;;
                        $.XB.window(settings.dialog)
                        break;
                    case '2'://无窗口打开
                        var ID = settings.rowid || $.XB.getallrowid();
                        //if (ID == 0) {
                        //    $.XB.warning({ "message": "很抱歉，请选择需要操作的数据行！" });
                        //    return false;
                        //}
                        $.ajax({
                            url: settings.dialog.url,
                            type: "post",
                            cache: false,
                            async: false,
                            data: { "ID": ID, "__RequestVerificationToken": settings.token },
                            success: function (data) {
                                data = $.parseJSON(data);
                                if (data.result) {
                                    if (typeof (settings.dialog.save) == "undefined" || typeof (settings.dialog.save.fn) == "string") {
                                        $.XB.success({ message: data.message, fn: function () { $.XB.reload({ "id": settings.datalist }); } });
                                    }
                                    else if (typeof (settings.dialog.save.fn) == "function") {
                                        settings.dialog.save.fn(data);
                                    }
                                }
                                else {
                                    $.XB.warning({ message: data.message });
                                }
                            },
                            error: function () {
                                $.XB.error({ "message": "操作失败，请刷新后重试" });
                            }
                        });
                        break;
                    case '3'://带确认的无窗口
                        var ID = settings.rowid || $.XB.getallrowid();
                        if (ID == 0) {
                            $.XB.warning({ "message": "很抱歉，请选择需要操作的数据行！" });
                            return false;
                        }
                        $.XB.confirm({
                            "message": "你确定操作选中的数据吗？点击确定为继续执行操作，点击取消则终止操作。", "fn": function () {
                                $.ajax({
                                    url: settings.dialog.url,
                                    type: "post",
                                    cache: false,
                                    async: false,
                                    data: { "ID": ID, "__RequestVerificationToken": settings.token },
                                    success: function (data) {
                                        data = $.parseJSON(data);
                                        if (settings.dialog.save != null && typeof (settings.dialog.save.fn) == "function") {
                                            settings.dialog.save.fn(data);
                                        }
                                        else {
                                            if (data.result) {
                                                $.XB.success({ message: data.message, fn: function () { $.XB.reload({ "id": settings.datalist }); } });
                                            }
                                            else {
                                                $.XB.warning({ message: data.message });
                                            }
                                        }
                                    },
                                    error: function () {
                                        $.XB.error({ "message": "操作失败，请刷新后重试" });
                                    }
                                });
                            }
                        });
                        break;
                    case '4'://新标签打开
                        var ID = settings.rowid || $.XB.getrowid();
                        if (ID == 0) {
                            $.XB.warning({ "message": "很抱歉，请选择需要操作的数据行！" });
                            return false;
                        }
                        settings.dialog.url = (settings.dialog.url.indexOf('?') > -1) ? settings.dialog.url + "&ID=" + ID : settings.dialog.url + "?ID=" + ID;

                        var tab = top.$('#MTabs').tabs('getSelected');
                        var title = tab.panel('options').title;

                        top.AddTag(title + ':' + settings.dialog.title, settings.dialog.url, settings.dialog.iconCls);
                        break;
                    case '5'://地址栏打开
                        var ID = settings.rowid || $.XB.getrowid();
                        if (ID == 0 && settings.type) {
                            $.XB.warning({ "message": "很抱歉，请选择要操作的数据行！" });
                            return false;
                        }
                        top.location.href = (settings.dialog.url.indexOf('?') > -1) ? settings.dialog.url + "&ID=" + ID : settings.dialog.url + "?ID=" + ID;
                        break;
                    case '6'://表单提交打开
                        $("#FF").attr("method", "post").attr("action", settings.dialog.url).submit();
                        break;
                    case '7'://打开后台返回链接
                        var ID = settings.rowid || $.XB.getallrowid();
                        if (ID == 0) {
                            $.XB.warning({ "message": "很抱歉，请选择需要操作的数据行！" });
                            return false;
                        }
                        $.XB.confirm({
                            "message": "你确定操作选中的数据吗？点击确定为继续执行操作，点击取消则终止操作。", "fn": function () {
                                $.ajax({
                                    url: settings.dialog.url,
                                    type: "post",
                                    cache: false,
                                    async: false,
                                    data: { "ID": ID, "__RequestVerificationToken": settings.token },
                                    success: function (data) {
                                        data = $.parseJSON(data);
                                        if (settings.dialog.save != null && typeof (settings.dialog.save.fn) == "function") {
                                            settings.dialog.save.fn(data);
                                        }
                                        else {
                                            if (data.result) {
                                                //$.XB.success({ message: data.message, fn: function () { $.XB.reload({ "id": settings.datalist }); } });
                                                window.open (data.message);
                                            }
                                            else {
                                                $.XB.warning({ message: data.message });
                                            }
                                        }
                                    },
                                    error: function () {
                                        $.XB.error({ "message": "操作失败，请刷新后重试" });
                                    }
                                });
                            }
                        });
                        break;
                }
                break;
            case 'all':
                $(settings.datalist).datagrid('checkAll');
                break;
            case 'clearall':
                $(settings.datalist).datagrid('uncheckAll');
                break;
            case 'anti':
                var $DataGrid = $(settings.datalist);
                var $selectrows = $DataGrid.datagrid('getSelections');
                $DataGrid.datagrid('checkAll');
                for (var i = 0; i < $selectrows.length; i++) {
                    $DataGrid.datagrid('uncheckRow', $DataGrid.datagrid('getRowIndex', $selectrows[i]));
                }
                break;
        }
    },
    //打开对话窗口
    //OK按钮执行固定的Save()，执行自定义的方法未实现
    dialog: function (options) {
        var settings = {
            "url": "",
            "title": "标题",
            "width": 750,
            "height": 450,
            "oktext": " 确 定 ",
            "canceltext": " 取 消 ",
            "fn": "",
            "divid": "#D1"
        };
        if (options) {
            $.extend(settings, options);
        }
        top.$.XB.creatediv({ "id": settings.divid });
        top.$(settings.divid).dialog({
            title: settings.title,
            width: settings.width,
            height: settings.height,
            content: '<iframe id="iframe" name="iframe" src="' + settings.url + '" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe>',
            cache: false,
            shadow: false,
            modal: true,
            buttons: [{
                text: settings.oktext,
                iconCls: "icon-ok",
                handler: function () {
                    if (typeof (settings.fn) == "string") {
                        top.$("#" + $(top.$(settings.divid).dialog("options").content).attr("id"))[0].contentWindow.$.XB.Save(settings.save);
                    }
                    else if (typeof (settings.fn) == "function") {
                        settings.fn();
                    }
                }
            }, {
                text: settings.canceltext,
                iconCls: "icon-back",
                handler: function () {
                    top.$(settings.divid).dialog('close');
                }
            }]
        });
    },
    //打开对话窗口
    OPUrl: function OPUrl(options) {
        var settings = {
            "url": "",
            "title": "标题",
            "width": 514,
            "height": 210,
            "fn": "",
            "divid": "#D1"
        };
        if (options) {
            $.extend(settings, options);
        }

        $.XB.creatediv({ "id": settings.divid });
        $(settings.divid).dialog({
            title: settings.title,
            width: settings.width,
            height: settings.height,
            content: '<iframe id="iframe" name="iframe" src="' + settings.url + '" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe>',
            cache: false,
            shadow: false,
            modal: true
        });
    },
    //对话窗口Form提交默认要执行的保存方法
    Save: function (options) {
        var settings = {
            "url": "Save",
            "form": "#FF",
            "datalist": "#DataList",
            "divid": "#D1",
            "fn": ""
        };
        if (options) {
            $.extend(settings, options);
        }
        parent.$.messager.progress();//提交前显示进度条
        if (settings.url.toLowerCase() == 'save') {
            settings.url = "../" + settings.url;
        }else {
            settings.url = "/admin.php/" + settings.url;
        }
        $(settings.form).form('submit', {
            url: settings.url,
            onSubmit: function () {
                var isValid = $(this).form('enableValidation').form('validate');
                if (!isValid) {
                    parent.$.messager.progress('close');//如果表单验证无效的则隐藏进度条
                }
                return isValid;//返回false终止表单提交
            },
            success: function (data) {
                parent.$.messager.progress('close');
                data = $.parseJSON(data);
                if (typeof (settings.fn) == "string") {
                    if (data.result==1) {
                        $.XB.success({
                            message: data.message, fn: function () {
                                $.XB.findiframe().$.XB.reload();
                                top.$(settings.divid).dialog('close');
                            }
                        });
                    }
                    else {
                        $.XB.warning({ message: data.message });
                    }
                }
                else if (typeof (settings.fn) == "function") {
                    settings.fn(data);
                }
            }
        });
    },
    //顶部对话框获得当前选中标签内置元素集
    findiframe: function () {
        var $tt = top.$('#MTabs');
        var tab = $tt.tabs('getSelected');
        var title = tab.panel('options').title;
        var index = $tt.tabs('getTabIndex', tab);
        if ($tt.tabs('exists', '' + title + '')) {
            $tt.tabs('select', '' + title + '');
            var ParentTab = $tt.tabs('getSelected');
            var ParentIndex = $tt.tabs('getTabIndex', ParentTab);
            ParentTab = top.$(".tabs-panels>div").get(ParentIndex);
           return $(ParentTab).find('iframe')[0].contentWindow;
        }
    },

    findiframe3: function () {
        var $tt = top.$('#MTabs');
        var tab = $tt.tabs('getSelected');
        var title = tab.panel('options').title;
        var index = $tt.tabs('getTabIndex', tab);
        if ($tt.tabs('exists', '' + title + '')) {
            $tt.tabs('select', '' + title + '');
            var ParentTab = $tt.tabs('getSelected');
            var ParentIndex = $tt.tabs('getTabIndex', ParentTab);
            ParentTab = top.$(".tabs-panels>div").get(ParentIndex);
            return $(ParentTab).find('iframe')[0].contentWindow;
        }
    },

    findiframe2: function (options) {
        var settings = {
            "divid": "#D1"
        };
        if (options) {
            $.extend(settings, options);
        }
        var $tt = top.$(settings.divid);
        if ($tt.is(":hidden") || $tt.length==0)
        {
            return $.XB.findiframe();
        }
        return $tt.find('iframe')[0].contentWindow;
    },
    //标签页面Form执行的保存方法
    pagesave: function (options) {
        var settings = {
            "url": "../save",
            "form": "#FF",
            "datalist": "#DataList",
            "divid": "#D1",
            "fn": "",
            "isiframe": false,
            "isClose":true
        };
        if (options) {
            $.extend(settings, options);
        }
        $.messager.progress();
        $(settings.form).form('submit', {
            url: settings.url,
            onSubmit: function () {
                var isValid = $(this).form('enableValidation').form('validate');
                if (!isValid) {
                    $.messager.progress('close');
                }
                return isValid;//返回false终止表单提交
            },
            success: function (data) {
                $.messager.progress('close');
                data = $.parseJSON(data);
                if (typeof (settings.fn) == "string") {
                    if (data.result) {
                        $.XB.success({
                            message: data.message, fn: function () {
                                var $tt = top.$('#MTabs');
                                var tab = $tt.tabs('getSelected');
                                var index = $tt.tabs('getTabIndex', tab);
                                var title = tab.panel('options').title.split(':')[0];

                                if ($tt.tabs('exists', title)) {
                                    $tt.tabs('select', title);
                                    var ParentTab = $tt.tabs('getSelected');
                                    var ParentIndex = $tt.tabs('getTabIndex', ParentTab);
                                    ParentTab = top.$(".tabs-panels>div").get(ParentIndex);
                                    if (settings.isiframe) {
                                        $(ParentTab).find('iframe').contents().find('iframe')[0].contentWindow.$.XB.reload();
                                    }
                                    else {
                                        $(ParentTab).find('iframe')[0].contentWindow.$.XB.reload();
                                    }
                                }
                                if (settings.isClose) {
                                    $tt.tabs('close', index);
                                }
                            }
                        });
                    }
                    else {
                        $.XB.warning({ message: data.message });
                    }
                }
                else if (typeof (settings.fn) == "function") {
                    settings.fn(data);
                }
            }
        });
    },
    //重置Easyui Form表单
    easyuireset: function (options) {
        var settings = {
            "form": "#FF"
        };
        if (options) {
            $.extend(settings, options);
        } $(settings.form).form('reset');
    },
    //打开窗口
    window: function (options) {
        var settings = {
            "divid": "#W1",
            "url": "",
            "title": "标题",
            "width": 750,
            "height": 450,
            "fn": function () { }
        };
        if (options) {
            $.extend(settings, options);
        }
        top.$.XB.creatediv({ "id": settings.divid });
        top.$(settings.divid).window({
            title: settings.title,
            minimizable: false,
            maximizable: false,
            collapsible: false,
            resizable: false,
            width: settings.width,
            height: settings.height,
            modal: true,
            content: '<iframe id="iframe" name="iframe" src="' + settings.url + '" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe>',
            onClose: settings.fn
        });
    },
    //C#中JSON的日期时间转成JS日期时间
    JSDateTime: function (s) {
        if (s != null && s.indexOf('Date') > -1) {
            var t = new Date(parseInt(s.slice(6, 19)));
            return t;
        }
    },
    //C#中JSON的日期时间转成日期时间字符型，列表用
    JSDateTimeStr: function (s) {
        if (s != null && s.indexOf('Date') > -1) {
            var t = new Date(parseInt(s.slice(6, 19)));
            return $.XB.FormatDate(t);
        }
    },
    //Int类型的排序默认值处理
    JSSortInt: function (value) {
        if (value != 2147483647) {
            return value;
        }
    },
    //更多条件显示隐藏
    moresearch: function (options) {
        var settings = {
            "datalist": "#DataList",
            "current": "#MoreSearch"
        };
        if (options) {
            $.extend(settings, options);
        }
        $('#stbody').toggle();
        $(settings.datalist).datagrid('resize', {
            height: $(window).height() - $('#tools').height() - $('#search').height() - 10
        });
        var T = $(settings.current);
        if (T.val() == "更多条件") {
            T.val("隐藏条件");
        } else {
            T.val("更多条件");
        }
    },
    //顶部搜索处理成Json数据重新加载列表
    search: function (options) {
        var settings = {
            "datalist": "#DataList",
            "search": "#search"
        };
        if (options) {
            $.extend(settings, options);
        }
        $(settings.datalist).datagrid('load', $(settings.search).FromDataSearch());
    },
    //顶部搜索处理成Json数据重新加载列表
    search2: function (options) {
        var settings = options;
        $(settings.datalist).datagrid('load', $(settings.search).FromDataSearch());
    },

    //回车触发搜索条件提交
    entertree: function () {
        $(document).keydown(function (e) {
            var e = e || event,
            keycode = e.which || e.keyCode;
            if (keycode == 13) {
                $.XB.searchtree();
            }
        });
    },
    treesearch: function (options) {
        var settings = {
            "datalist": "#DataList",
            "search": "#search"
        };
        if (options) {
            $.extend(settings, options);
        }
        $(settings.datalist).treegrid('load', $(settings.search).FromDataSearch());
    },
    //顶部搜索处理成TreeGrid数据重新加载列表
    searchtree: function (options) {
        var settings = {
            "datalist": "#DataList",
            "search": "#search"
        };
        if (options) {
            $.extend(settings, options);
        }
        $(settings.datalist).treegrid('load', $(settings.search).FromDataSearch());
    },
    //datagrid封装处理函数
    datagrid: function (options) {
        var settings = {
            "datalist": "#DataList",
            "url": "DataList",
            "u":"",
            "pagesize": 40,
            "frozenColumns": [],
            "columns": [],
            "showfooter": false,
            "queryParams":{},
            "rowcontextmenu": function (e, rowIndex, rowData) {
                e.preventDefault();
                $(this).datagrid('uncheckAll').datagrid('selectRow', rowIndex);
                $("#DataListMenu").menu({
                    onClick: function (item) {
                        OpenWin(item.name);
                    }
                }).menu('show', {
                    left: e.pageX,
                    top: e.pageY
                });
            },
            "dblslickrow": function (rowIndex, rowData) {
                if(settings.del=="del"){
                    Cell_remove(rowData.ID);
                }else {
                    $(this).datagrid('uncheckAll').datagrid('selectRow', rowIndex);
                    OpenWin('edit');
                }
            },
            "loadsuccess": function () {
                $(this).datagrid('resize', {
                    height: $(window).height() - $('#tools').height() - $('#search').height() - 10
                });
            }
        };
        if (options) {
            $.extend(settings, options);
        }

        if(settings.u){
            url= settings.url + "?" + settings.u;
        }else{
            url=settings.url;
        }
        $(settings.datalist).datagrid({
            url: url,
            frozenColumns: [settings.frozenColumns],
            columns: [settings.columns],
            pageSize: settings.pagesize,
            border: 0,
            rownumbers: true,
            pagination: true,
            showFooter: settings.showfooter,//是否显示页脚
            autoRowHeight: false,
            striped: true,
            queryParams:settings.queryParams,
            onLoadSuccess: settings.loadsuccess,//数据列表加载成功执行
            onDblClickRow: settings.dblslickrow,//双击数据行执行
            onRowContextMenu: settings.rowcontextmenu//右键菜单执行
        });
    },
    //Combobox显示面板前处理函数
    showpanel: function (options) {
        var settings = {
            "current": null,
            "rely": null,
            "message": "很抱歉，请先选择依赖项"
        };
        if (options) {
            $.extend(settings, options);
        }
        if ($(settings.rely).combobox('getValue').length == 0) {
            $(settings.current).combo('hidePanel'); $.XB.warning({ 'message': settings.message })
        }
    },
    //文件上传
    upload: function (options) {
        var host=window.location.pathname;
        var arr=host.split('/');
        var settings = {
            "id": "#uploader",
            "path": "pic",//保存路径是图片(pic)还是文件(file)
            "ext": "jpg,jpeg,png,gif,bmp,zip,rar,doc,xls,ppt,docx,xlsx,ppts,txt",//扩展名
            "ismulti": true,//是否支持多选
            "file": "pic",
            "maxsize": "10MB",
            "url": arr[0]+'/'+arr[1]+'/Attachment/File/Upload'
        };
        if (options) {
            $.extend(settings, options);
        }
        var uploader = $(settings.id).pluploadQueue({
            runtimes: 'html5,flash,html4',
            url: settings.url,
            chunk_size: '1MB',//分块上传，0不分块
            multipart_params: { "Path": settings.path,"file":settings.file,"NameAdd": $.XB.FormatDate(new Date(),"ddhhmmssS_") },//附加参数
            multi_selection: settings.ismulti,//是否支持多选
            flash_swf_url: '/JS/Plupload/Moxie.swf',
            filters: {
                mime_types: [
                  { title: "select files", extensions: settings.ext },
                ],
                max_file_size: settings.maxsize,
                prevent_duplicates: true //是否允许选取重复文件
            },
            init: {
                FilesAdded: function (uploader, files) {
                    if (!settings.ismulti) {
                        uploader.start();
                    }
                },
                FileUploaded: function (uploader, queuefile, response) {//队列某一文件上传完成触发
                    //新增代码 文件类型错误提示  start
                    var res=$.parseJSON(response.response);

                    if(res.result='0'&& typeof res.result !="undefined"){
                        alert(res.message);
                    }
                    //新增代码 文件类型错误提示   end
                    if (settings.ismulti==='false'){
                       $.XB.findiframe2().$('#' + settings.file).textbox('setValue', $.parseJSON(response.response).FilePath);
                    }
                },
                UploadComplete: function (uploader, files) {//队列所有文件上传完成触发
                    parent.$('#W1').window('close');
                },
                Error: function (uploader, errObject) {
                    if (errObject.code == "-600") {
                        $.XB.error({ "message": "" + errObject.file.name + "文件超过系统限制的" + settings.maxsize });
                    }
                    else {
                        $.XB.error({ "message": errObject.message });
                    }
                }
            }
        });
    },
    //图片预览
    pictips: function (options) {
        var host=window.location.pathname;
        var arr=host.split('/');
        var settings = {
            "id": "#dd",
            "path": "#Pic"
        };
        if (options) {
            $.extend(settings, options);
        }
        $(settings.id).tooltip({
            hideEvent: 'none',
            content: '',
            onShow: function () {
                var t = $(this);
                var c = $(settings.path).textbox('getValue');
                if (c.length == 0) {
                    t.tooltip('update', '预览失败，请先上传图片。');
                }
                else {
                    t.tooltip('update', '<a href="' + c + '" target="_blank" title="点击查看原始图片"><img src="'+ c + '" alt="点击查看原始图片" width="100" height="100" /></a>');
                }
                t.tooltip('tip').focus().unbind().bind('blur', function () {
                    t.tooltip('hide');
                });
            }
        });
    }, 
    //将日期格式化成指定格式的字符串
    FormatDate: function (date, fmt) {
        /*
        y（年）
        M（月）
        d（日）
        q（季度）
        w（星期）
        H（24小时制的小时）
        h（12小时制的小时）
        m（分钟）
        s（秒）
        S（毫秒）
        另外，字符的个数决定输出字符的长度，如，yy输出16，yyyy输出2016，ww输出周五，www输出星期五        
        @param date 必须是日期格式
        @param fmt 字符串格式，默认：yyyy-MM-dd HH:mm:ss
        @returns 返回格式化后的日期字符串
        */

        fmt = fmt || 'yyyy-MM-dd HH:mm:ss';
        var obj =
        {
            'y': date.getFullYear(), // 年份，注意必须用getFullYear
            'M': date.getMonth() + 1, // 月份，注意是从0-11
            'd': date.getDate(), // 日期
            'q': Math.floor((date.getMonth() + 3) / 3), // 季度
            'w': date.getDay(), // 星期，注意是0-6
            'H': date.getHours(), // 24小时制
            'h': date.getHours() % 12 == 0 ? 12 : date.getHours() % 12, // 12小时制
            'm': date.getMinutes(), // 分钟
            's': date.getSeconds(), // 秒
            'S': date.getMilliseconds() // 毫秒
        };
        var week = ['日', '一', '二', '三', '四', '五', '六'];
        for (var i in obj) {
            fmt = fmt.replace(new RegExp(i + '+', 'g'), function (m) {
                var val = obj[i] + '';
                if (i == 'w') return (m.length > 2 ? '星期' : '周') + week[val];
                for (var j = 0, len = val.length; j < m.length - len; j++) val = '0' + val;
                return m.length == 1 ? val : val.substring(val.length - m.length);
            });
        }
        return fmt;
    },
        //Combotree树形控件
    combotreedata: function (T, Arr, V, Url) {//辅助Combotree加载数据
        $.ajax({
            url: Url,
            dataType: 'json',
            success: function (data) {
                data = eval(data);
                Arr = Arr.concat(data);
                $(T).combotree("loadData", Arr);
                if (V.length > 0) {
                    $(T).combotree('setValue', V)
                }
            }
        });
    },
    ComboData: function (T, Arr, V, Url) {//辅助Combobox加载数据
        $.ajax({
            url: Url,
            dataType: 'json',
            success: function (data) {
                data = eval(data);
                Arr = Arr.concat(data);
                $(T).combobox("loadData", Arr);
                if (V.length > 0) {
                    $(T).combobox('setValue', V)
                }
            }
        });
    },
	   //创建DIV
    AddDiv: function AddDiv() {
        var count = $('#w1').length;
        var name = "w1";
        if (count >= 0) {
            name = "w" + count + 2;
        }
        var testDv = document.createElement("div");
        testDv.id = name
        document.body.appendChild(testDv);
        $('#' + name).addClass('NoScroll');
        $('#' + name).attr("name", "win");
        return name;
    },
    CorOPUrl: function CorOPUrl(Url, T, W, H, icon, wi, he) {//右下角显示
        var name =$.XB.AddDiv();
        if (icon == undefined) {
            icon = "";
        }
        if (wi == undefined) {
            wi = 625;
        }
        if (he == undefined) {
            he = 330;
        }
        $('#' + name).window({
            title: T,
            minimizable: false,
            maximizable: false,
            collapsible: false,
            top: ($(window).height() - he),
            left: ($(window).width() - wi),
            draggable: false,
            iconCls: icon,
            resizable: true,
            width: W,
            height: H,
            modal: true,
            content: '<iframe id="iframe" name="iframe" src="' + Url + '" width="100%" height="100%" frameborder="0" scrolling="yes"></iframe>'
        });
    }
};

var Common = {
    formatName: function formatName(value, row, index) {
        //在名称前面显示图标就是靠iconCls属性，iconCls属性为一个css类，easyui拿到这个属性值就能显示相应的图标了
        //由于传递过来的json字符串中未包含iconCls属性，只有icon属性，所以要想easyui显示图标只需将icon的值赋给iconCls
        row.iconCls = row.Icon;
        return value;
    },
    //EasyUI用DataGrid用日期格式化
    TimeFormatter: function (value, rec, index) {
        if (value == undefined) {
            return "";
        }
        /*json格式时间转js时间格式*/
        value = value.substr(1, value.length - 2);
        var obj = eval('(' + "{Date: new " + value + "}" + ')');
        var dateValue = obj["Date"];
        if (dateValue.getFullYear() < 1900) {
            return "";
        }
        return formatDate(dateValue, "yyyy-MM-dd HH:mm:ss");
    },
    DateTimeFormatter: function (value, rec, index) {
        if (value == undefined) {
            return "";
        }
        /*json格式时间转js时间格式*/
        value = value.substr(1, value.length - 2);

        var obj = eval('(' + "{Date: new " + value + "}" + ')');

        var dateValue = obj["Date"];
        if (dateValue.getFullYear() < 1900) {
            return "";
        }
        return formatDate(dateValue, "yyyy-MM-dd");
    },
    //EasyUI用DataGrid用日期格式化
    DateFormatter: function (value, rec, index) {
        if (value == undefined) {
            return "";
        }
        /*json格式时间转js时间格式*/
        value = value.substr(1, value.length - 2);
        var obj = eval('(' + "{Date: new " + value + "}" + ')');
        var dateValue = obj["Date"];
        if (dateValue.getFullYear() < 1900) {
            return "";
        }
        return formatterDate();
    },
   
    StatusFormatter: function (value, rec, index) {
        return value == "1" ? "<span class=\"Green\">启用</span>" : "<span class=\"Hui\">禁用</span>";
    },
    SexFormatter: function (value, rec, index) {
        return value == "1" ? "男" :value == "2"?"女":"保密";
    },
    DoFormatter: function (value, rec, index) {
        return value == "1" ? "<span style='color:green;'>是</span>" : "否";
    },
    AuditFormatter: function (value, rec, index) {
        if(value=="3"){
            return "<span style='color:red;'>审核中</span>";
        }else if(value=="2"){
            return "<span style='color:orange;'>失败</span>";
        }else if(value=="4"){
            return "<span style='color:orange;'>申请提交</span>";
        }else if(value=="1"){
            return "<span style='color:green;'>已通过</span>";
        }else{
            return "";
        }
    },
    StateFormatter: function (value, rec, index) {
        if(value=="0"){
            return "<span style='color:red;'>未验证</span>";
        }else if(value=="1"){
            return "<span style='color:orange;'>验证中</span>";
        }else{
            return "<span style='color:green;'>已验证</span>";
        }
    },
    SomeFormatter: function (value, rec, index) {
        return value == "是" ? "<span style='color:red;'>是</span>" : value;
    },
    NullFormatter: function (value, rec, index) {
        return value == null ? "<span style='color:red;'>根节点</span>" : value;
    },
    NewFormatter: function (value, rec, index) {
        return value == "单页" ? "<span style='color:red;'>" + value + "</span>" : value;
    },
    WhetherFormatter: function (value, rec, index) {
        return value == "正常" ? "<span style='color:red;'>正常</span>" : value;
    },

    ImgFormatter: function (value, rec, index) {
        return '<img src="' + value + '" width="80"  />';
    },
    PackTypeFomatter: function (value, rec, index) {
        return Config.PackType[value];
    },

    delHtmlTagFormatter: function (value, rec, index) {
        if (value == undefined) {
            return "";
        }
        return value.replace(/<[^>]+>/g, "");
    }

};

///日期转换yyyy-MM-dd HH:mm:ss
function formatDate(date, formate) {
    if (formate == undefined) {
        formate = "yyyy-MM-dd HH:mm";
    }
    var year = date.getFullYear();
    var month = date.getMonth() + 1;
    var day = date.getDate();
    var hour = date.getHours();
    var minute = date.getMinutes();
    var second = date.getSeconds();
    if (formate == "yyyy-MM-dd HH:mm:ss") {
        return year + "-" + formatTen(month) + "-" + formatTen(day) + " " + formatTen(hour) + ":" + formatTen(minute) + ":" + formatTen(second);
    }
    if (formate == "yyyy-MM-dd HH:mm") {
        return year + "-" + formatTen(month) + "-" + formatTen(day) + " " + formatTen(hour) + ":" + formatTen(minute);
    }
    return year + "-" + formatTen(month) + "-" + formatTen(day);
}
function formatTen(num) {
    return num > 9 ? (num + "") : ("0" + num);
}





 


//全部替换
String.prototype.replaceAll = function (s1, s2) {
    return this.replace(new RegExp(s1, "gm"), s2);
}

//解决IE8不支持Object.keys
var DONT_ENUM = "propertyIsEnumerable,isPrototypeOf,hasOwnProperty,toLocaleString,toString,valueOf,constructor".split(","), hasOwn = ({}).hasOwnProperty;
for (var i in {
    toString: 1
}) {
    DONT_ENUM = false;
}
Object.keys = Object.keys || function (obj) {//ecma262v5 15.2.3.14
    var result = [];
    for (var key in obj) if (hasOwn.call(obj, key)) {
        result.push(key);
    }
    if (DONT_ENUM && obj) {
        for (var i = 0 ; key = DONT_ENUM[i++];) {
            if (hasOwn.call(obj, key)) {
                result.push(key);
            }
        }
    }
    return result;
};
//打印数组对象
function writeObj(obj){
    var description = "";
    for(var i in obj){
        var property=obj[i];
        description+=i+" = "+property+"\n";
    }
    alert(description);
}


