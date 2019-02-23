function combine(arr) {
    var r = [];
    (function f(t, a, n) {
        if (n == 0) return r.push(t);
        for (var i = 0; i < a[n - 1].length; i++) {
            f(t.concat(a[n - 1][i]), a, n - 1);
        }
    })([], arr, arr.length);
    return r;
}
var arr = [
    ['1', '2'],
    ['a', 'b', 'c'],
    ['y', 'z']];
var res = combine(arr);

//合并单元格
var row = [];
var rowspan = res.length;
for (var n = arr.length - 1; n > -1; n--) {
    row[n] = parseInt(rowspan / arr[n].length);
    rowspan = row[n];
}
row.reverse();

//table tr td
var str = "";
var len = res[0].length;
for (var i = 0; i < res.length; i++) {
    var tmp = "";
    for (var j = 0; j < len; j++) {
        if (i % row[j] == 0 && row[j] > 1) {
            tmp += "<td rowspan='" + row[j] + "'>" + res[i][j] + "</td>";
        } else if (row[j] == 1) {
            tmp += "<td>" + res[i][j] + "</td>";
        }
    }
    str += "<tr>" + tmp + "<td>xxx</td>" + "<td>xxx</td>" + "</tr>";
}

//thead
var th = "";
for (var k = 0; k < len; k++) {
    th += "<th>" + k + "</th>";
}
th = "<thead>" + th + "<th>价格</th>" + "<th>数量</th>" + "</thead>";
str = "<table>" + th + str + "</table>";
$("#mb").empty().html(str);