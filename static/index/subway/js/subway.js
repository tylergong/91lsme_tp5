function change_start_line(obj) {
	var strs = obj.value.split("_");
	var data = "cid=" + strs[0] + "&line=" + strs[1];
	$.ajax({
		type: "GET",
		url: "/index/subway/getsitebyline/",
		data: data,
		dataType: "json",
		success: function(data) {
			var _h = "<option value=0>--请选择起始站点--</option>";
			if (data) {
				$.each(data,
						function(k, v) {
							_h += "<option value=" + k + ">" + v + "</option>"
						})
			}
			$("#start_site").html(_h)
		}
	})
}
function change_end_line(obj) {
	var strs = obj.value.split("_");
	var data = "cid=" + strs[0] + "&line=" + strs[1];
	$.ajax({
		type: "GET",
		url: "/index/subway/getsitebyline/",
		data: data,
		dataType: "json",
		success: function(data) {
			var _h = "<option value=0>--请选择终点站点--</option>";
			if (data) {
				$.each(data,
						function(k, v) {
							_h += "<option value=" + k + ">" + v + "</option>"
						})
			}
			$("#end_site").html(_h)
		}
	})
}
function query_line() {
	var s_s, e_s;
	s_s = $("#start_site").val();
	e_s = $("#end_site").val();
	var name1 = $("#start_site").find("option:selected").text();
	var name2 = $("#end_site").find("option:selected").text();
	if (empty_c(s_s) || empty_c(e_s)) {
		$("#resText").html("请选择好起点和终点，再点击查询哦。").addClass("red");
		return;
	} else {
		var data = "s_s=" + s_s + "&e_s=" + e_s;
		$.ajax({
			type: "GET",
			url: "/index/subway/submit/",
			data: data,
			dataType: "json",
			success: function(data) {
				if (data.error != 0) {
					$("#resText").html(data.message).addClass("red")
				} else {
					var _html = "from " + name1 + " to " + name2 + " : (一共 " + data.count + " 种换乘方式) [ 计算如有误差，请以真实价格为准 ]<br/>";
					_html += "<span style='color:red;'>最短距离：</span>" + data.min + "<br/>";
					_html += "<span style='color:red;'>最少换乘：</span>" + data.min2 + "<br/><br/>";
					//$.each(data.allline,
					//		function(k, v) {
								//_html += "[<b style='color:red;'>" + (k) + "</b>]" + v + "<br/>"
					//		})
				}
				$("#resText").html(_html).removeClass("red")
			}
		})
	}
}
function empty_c(str) {
	return (str == "" || str == null || str == undefined || str == 0) ? true : false
}