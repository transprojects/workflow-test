<?php
set_time_limit(0);
$edit_latex_url_new = 'https://editlatex-dev.transforma.in/';
$edit_latex_url_new .=$app_res['editlatex_token']."?token=".$app_res['editlatex_project_id'];
?>

<a type="button"  class="btn btn-danger" id="ceButton" href="javascript:void(0);"><i class="ace-icon fa fa-edit align-top bigger-125"></i> New Latex Editor</a>

<script>
/* Own full-screen loader: does not depend on the page's #ajaxLoader_* boxes / overlay (a themed page can hide those) */
(function () {
    function elBusy(text) {
        if (document.getElementById('el-busy')) { return; }
        if (!document.getElementById('el-busy-css')) {
            var st = document.createElement('style');
            st.id = 'el-busy-css';
            st.textContent = '@keyframes elspin{to{transform:rotate(360deg)}}' +
                '#el-busy{position:fixed;top:0;right:0;bottom:0;left:0;z-index:2147483000;display:flex;align-items:center;justify-content:center;background:rgba(20,48,74,.55);font-family:inherit;}' +
                '#el-busy .box{display:flex;flex-direction:column;align-items:center;gap:6px;padding:26px 36px;border-radius:16px;background:#fff;box-shadow:0 24px 60px rgba(20,48,74,.4);text-align:center;max-width:90vw;}' +
                '#el-busy .sp{width:46px;height:46px;border-radius:50%;border:5px solid #e6ebf1;border-top-color:#F56500;border-right-color:#1b6ca8;animation:elspin .8s linear infinite;margin-bottom:8px;}' +
                '#el-busy b{color:#14304a;font-size:16px;} #el-busy span{color:#6b7785;font-size:13px;}';
            document.head.appendChild(st);
        }
        var d = document.createElement('div');
        d.id = 'el-busy';
        d.setAttribute('role', 'alert');
        d.innerHTML = '<div class="box"><div class="sp"></div><b></b><span>This can take a little while - please wait…</span></div>';
        d.querySelector('b').textContent = text;
        document.body.appendChild(d);
    }
    function elDone() {
        var d = document.getElementById('el-busy');
        if (d && d.parentNode) { d.parentNode.removeChild(d); }
    }

    document.getElementById('ceButton').addEventListener('click', function () {
        const editlatexToken = "<?php echo $app_res['editlatex_token'] ?? ''; ?>";
        const pubId = "<?php echo $app_res['pub_id']; ?>";
        const vf = "<?php echo $app_res['volume_folder']; ?>";
        const stage = "<?php echo $app_res['stage']; ?>";
        const custName = "<?php echo $customer_info['cust_name']."_".$journal_info['j_code']; ?>";

        if (editlatexToken) {
            const projectId = "<?php echo $app_res['editlatex_project_id']; ?>";
            const url = "https://editlatex-dev.transforma.in/" + projectId + "?token=" + editlatexToken;
            window.open(url, "_blank");
            return;
        }

        /* open the editor tab NOW (inside the click): a window opened after the request finishes is usually blocked by the browser */
        var win = window.open('', '_blank');
        if (win) { try { win.document.write('<p style="font-family:sans-serif;padding:24px;color:#14304a">Generating the Edit LaTeX link, please wait…</p>'); } catch (e) {} }

        elBusy('Generating the Edit LaTeX link…');
        if (window.jQuery) { jQuery("#ajaxLoader_Editlatex").show(); jQuery("#overlay").show(); }   /* pages that still use their own loader */

        function finish() {
            elDone();
            if (window.jQuery) { jQuery("#ajaxLoader_Editlatex").hide(); jQuery("#overlay").hide(); }
        }
        function fail(msg) {
            finish();
            if (win) { try { win.close(); } catch (e) {} }
            alert(msg);
        }

        fetch(`<?php echo $_COOKIE['set_url']; ?>/editlatex_dev.php?pub_id=${encodeURIComponent(pubId)}&vf=${encodeURIComponent(vf)}&stage=${encodeURIComponent(stage)}&custname=${encodeURIComponent(custName)}`)
            .then(function (response) { return response.text(); })
            .then(function (txt) {
                var data = null;
                try { data = JSON.parse(txt); } catch (e) {}
                if (data && data.status === "success" && data.editlatex_token && data.editlatex_project_id) {
                    finish();
                    const newUrl = "https://editlatex-dev.transforma.in/" + data.editlatex_project_id + "?token=" + data.editlatex_token;
                    if (win) { win.location.href = newUrl; } else { window.open(newUrl, "_blank"); }
                } else {
                    console.error("Unexpected response:", txt);
                    fail("Failed to generate token." + (data && data.message ? "\n" + data.message : (data ? "" : "\nThe server did not return a valid answer.")));
                }
            })
            .catch(function (error) {
                console.error("Fetch error:", error);
                fail("Error occurred while generating token.");
            });
    });
})();
</script>
