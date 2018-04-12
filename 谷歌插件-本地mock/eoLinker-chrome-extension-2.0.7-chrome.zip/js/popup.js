$(document).on('click', '.btn-close', function() {
    // 手动关拓展
    // var result = confirm('确认关闭插件？关闭插件后请刷新eoLinker产品页面');
    // if (result == false) return;
    chrome.management.setEnabled(chrome.runtime.id, false, function callback() {})
})