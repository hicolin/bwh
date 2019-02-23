// ��ʽ����ʾ����
$(function() {
    function _formatNum(num, toMoney) {
        num = num + '';
        var hasDot = num.indexOf('.') >= 0, suffix = '', _num = num;
        // ��С����
        if (hasDot) {
            suffix = num.substr(num.indexOf('.'));
            _num = num.substr(0, num.indexOf('.'));
        }
        var temp = _num.split('').reverse();
        // ��������ÿ��3������һ������
        for (var i = j = 0, len = temp.length; i < len; i += 3) {
            if (i) {
                temp.splice(i + j, 0, ',');
                j++;
            }
        }
        temp = temp.reverse().join('');
        if (toMoney) {
            suffix = suffix || '';
            suffix = ( suffix + '' ).substr(0, 3);
            return temp + suffix;
        }
        return temp;
    }

    // ����������Ч
    function _increment(num, callback, opts) {
        var _num = temp = 0;
        opts = opts || {format: false};
        num = parseFloat(num);
        var step = Math.ceil(num / ( opts.times || 100 ));
        callback = callback || function () {
            };
        var timer = setInterval(function () {
            _num += step;
            if (num <= _num) {
                clearInterval(timer);
                _num = num;
            }
            temp = opts.format ? _formatNum(_num, true) : _num;
            callback(temp, _num >= num);
        }, 15);
    }

    // Ͷ��������ͳ��
    var label1 = $('.invest-users');
    _increment(label1.html(), function (num) {
        label1.html(num);
    }, {times: 15});
    // Ͷ�ʽ�������Լ�����׼����ͳ��
    $('.invest-money-data').each(function () {
        var $this = $(this);
        var value = $this.html().replace(/,/g, '');
        _increment(value, function (num) {
            $this.html(num);
        }, {format: true});
    });
});