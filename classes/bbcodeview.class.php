<?

// TODO by qwerty 整个bbcode编辑区应该都是组件，而不仅仅是工具栏
class BBCodeView {
    public static function render_bbcode_textarea() {
?>
        <div class="u-bbcodeEditor">
            <div class="BBCodeToolbar">
                <!--<span data-cmd="mediainfo"><svg xmlns="http://www.w3.org/2000/svg" viewBox="1 1 22 22" width="16" height="16"><path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"></path></svg></span>-->
                <span class="BBCodeToolbar-button" data-cmd="bold">
                    <?= icon('BBCodeToolbar/bold', 'BBCodeToolbar-icon') ?>
                </span>
                <span class="BBCodeToolbar-button" data-cmd="italic">
                    <?= icon('BBCodeToolbar/italic', 'BBCodeToolbar-icon') ?>
                </span>
                <span class="BBCodeToolbar-button" data-cmd="underline">
                    <?= icon('BBCodeToolbar/underline', 'BBCodeToolbar-icon') ?>
                </span>
                <span class="BBCodeToolbar-button" data-cmd="strikethrough">
                    <?= icon('BBCodeToolbar/strikethrough', 'BBCodeToolbar-icon') ?>
                </span>
                <span class="BBCodeToolbar-button" data-cmd="image">
                    <?= icon('BBCodeToolbar/image', 'BBCodeToolbar-icon') ?>
                </span>
                <!--<span data-cmd="video"><svg xmlns="http://www.w3.org/2000/svg" viewBox="1 1 22 22" unselectable="on"><path d="M10,16.5V7.5L16,12M20,4.4C19.4,4.2 15.7,4 12,4C8.3,4 4.6,4.19 4,4.38C2.44,4.9 2,8.4 2,12C2,15.59 2.44,19.1 4,19.61C4.6,19.81 8.3,20 12,20C15.7,20 19.4,19.81 20,19.61C21.56,19.1 22,15.59 22,12C22,8.4 21.56,4.91 20,4.4Z"></path></svg></span>-->
                <!--<span data-cmd="link"><svg xmlns="http://www.w3.org/2000/svg" viewBox="1 1 22 22" unselectable="on"><path d="M16,6H13V7.9H16C18.26,7.9 20.1,9.73 20.1,12A4.1,4.1 0 0,1 16,16.1H13V18H16A6,6 0 0,0 22,12C22,8.68 19.31,6 16,6M3.9,12C3.9,9.73 5.74,7.9 8,7.9H11V6H8A6,6 0 0,0 2,12A6,6 0 0,0 8,18H11V16.1H8C5.74,16.1 3.9,14.26 3.9,12M8,13H16V11H8V13Z"></path></svg></span>-->
                <!--<span data-cmd="unorderedList"><svg xmlns="http://www.w3.org/2000/svg" viewBox="1 1 22 22" unselectable="on"><path d="M7,5H21V7H7V5M7,13V11H21V13H7M4,4.5A1.5,1.5 0 0,1 5.5,6A1.5,1.5 0 0,1 4,7.5A1.5,1.5 0 0,1 2.5,6A1.5,1.5 0 0,1 4,4.5M4,10.5A1.5,1.5 0 0,1 5.5,12A1.5,1.5 0 0,1 4,13.5A1.5,1.5 0 0,1 2.5,12A1.5,1.5 0 0,1 4,10.5M7,19V17H21V19H7M4,16.5A1.5,1.5 0 0,1 5.5,18A1.5,1.5 0 0,1 4,19.5A1.5,1.5 0 0,1 2.5,18A1.5,1.5 0 0,1 4,16.5Z"></path></svg></span>-->
                <!--<span data-cmd="orderedList"><svg xmlns="http://www.w3.org/2000/svg" viewBox="1 1 22 22" unselectable="on"><path d="M7,13H21V11H7M7,19H21V17H7M7,7H21V5H7M2,11H3.8L2,13.1V14H5V13H3.2L5,10.9V10H2M3,8H4V4H2V5H3M2,17H4V17.5H3V18.5H4V19H2V20H5V16H2V17Z"></path></svg></span>-->
                <span class="BBCodeToolbar-button" data-cmd="alignLeft">
                    <?= icon('BBCodeToolbar/alignLeft', 'BBCodeToolbar-icon') ?>
                </span>
                <span class="BBCodeToolbar-button" data-cmd="alignCenter">
                    <?= icon('BBCodeToolbar/alignCenter', 'BBCodeToolbar-icon') ?>
                </span>
                <span class="BBCodeToolbar-button" data-cmd="alignRight">
                    <?= icon('BBCodeToolbar/alignRight', 'BBCodeToolbar-icon') ?>
                </span>
                <span class="BBCodeToolbar-button" data-cmd="quote">
                    <?= icon('BBCodeToolbar/quote', 'BBCodeToolbar-icon') ?>
                </span>
                <span class="BBCodeToolbar-button" data-cmd="code">
                    <?= icon('BBCodeToolbar/code', 'BBCodeToolbar-icon') ?>
                </span>
                <!--<span data-cmd="emoji"><svg xmlns="http://www.w3.org/2000/svg" viewBox="1 1 22 22" unselectable="on"><path d="M12,17.5C14.33,17.5 16.3,16.04 17.11,14H6.89C7.69,16.04 9.67,17.5 12,17.5M8.5,11A1.5,1.5 0 0,0 10,9.5A1.5,1.5 0 0,0 8.5,8A1.5,1.5 0 0,0 7,9.5A1.5,1.5 0 0,0 8.5,11M15.5,11A1.5,1.5 0 0,0 17,9.5A1.5,1.5 0 0,0 15.5,8A1.5,1.5 0 0,0 14,9.5A1.5,1.5 0 0,0 15.5,11M12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20M12,2C6.47,2 2,6.5 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"></path></svg></span>-->
                <!--<span data-cmd="color"><svg xmlns="http://www.w3.org/2000/svg" viewBox="1 1 22 22" unselectable="on"><path d="M9.62,12L12,5.67L14.37,12M11,3L5.5,17H7.75L8.87,14H15.12L16.25,17H18.5L13,3H11Z"></path><path class="sce-color" d="M0,24H24V20H0V24Z" style="fill: inherit;"></path></svg></span>-->
                <!--<span data-cmd="size"><svg xmlns="http://www.w3.org/2000/svg" viewBox="1 1 22 22" unselectable="on"><path d="M3,12H6V19H9V12H12V9H3M9,4V7H14V19H17V7H22V4H9Z"></path></svg></span>-->
                <!--<span data-cmd="font"><svg xmlns="http://www.w3.org/2000/svg" viewBox="1 1 22 22" unselectable="on"><path d="M17,8H20V20H21V21H17V20H18V17H14L12.5,20H14V21H10V20H11L17,8M18,9L14.5,16H18V9M5,3H10C11.11,3 12,3.89 12,5V16H9V11H6V16H3V5C3,3.89 3.89,3 5,3M6,5V9H9V5H6Z"></path></svg></span>-->
                <!--<span data-cmd="source"><svg xmlns="http://www.w3.org/2000/svg" viewBox="1 1 22 22" unselectable="on"><path d="M14.6,16.6L19.2,12L14.6,7.4L16,6L22,12L16,18L14.6,16.6M9.4,16.6L4.8,12L9.4,7.4L8,6L2,12L8,18L9.4,16.6Z"></path></svg></span>-->
                <span class="BBCodeToolbar-button" data-cmd="comparison">
                    <?= icon('BBCodeToolbar/comparison', 'BBCodeToolbar-icon') ?>
                </span>
            </div>
        </div>
<?
    }
}
