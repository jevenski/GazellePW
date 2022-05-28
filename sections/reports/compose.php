<?
if (!check_perms('site_moderate_forums')) {
    error(403);
}

if (empty($Return)) {
    $ToID = $_GET['to'];
    if ($ToID == $LoggedUser['ID']) {
        error(Lang::get('reports', 'you_cannot_start_a_conversation_with_yourself'));
        header('Location: inbox.php');
    }
}

if (!$ToID || !is_number($ToID)) {
    error(404);
}

$ReportID = $_GET['reportid'];
$Type = $_GET['type'];
$ThingID = $_GET['thingid'];

if (!$ReportID || !is_number($ReportID) || !$ThingID || !is_number($ThingID) || !$Type) {
    error(403);
}

if (!empty($LoggedUser['DisablePM']) && !isset($StaffIDs[$ToID])) {
    error(403);
}

$DB->query("
	SELECT Username
	FROM users_main
	WHERE ID = '$ToID'");
list($ComposeToUsername) = $DB->next_record();
if (!$ComposeToUsername) {
    error(404);
}
View::show_header(Lang::get('reports', 'compose'), 'inbox,bbcode', 'PageReportCompose');

// $TypeLink is placed directly in the <textarea> when composing a PM
switch ($Type) {
    case 'user':
        $DB->query("
			SELECT Username
			FROM users_main
			WHERE ID = $ThingID");
        if (!$DB->has_results()) {
            $Error = Lang::get('reports', 'no_user_with_the_reported_id_found');
        } else {
            list($Username) = $DB->next_record();
            $TypeLink = "the user [user]{$Username}[/user]";
            $Subject = 'User Report: ' . display_str($Username);
        }
        break;
    case 'request':
    case 'request_update':
        $DB->query("
			SELECT Title
			FROM requests
			WHERE ID = $ThingID");
        if (!$DB->has_results()) {
            $Error = Lang::get('reports', 'no_request_with_the_reported_id_found');
        } else {
            list($Name) = $DB->next_record();
            $TypeLink = 'the request [url=' . site_url() . "requests.php?action=view&amp;id=$ThingID]" . display_str($Name) . '[/url]';
            $Subject = 'Request Report: ' . display_str($Name);
        }
        break;
    case 'collage':
        $DB->query("
			SELECT Name
			FROM collages
			WHERE ID = $ThingID");
        if (!$DB->has_results()) {
            $Error = Lang::get('reports', 'no_collage_with_the_reported_id_found');
        } else {
            list($Name) = $DB->next_record();
            $TypeLink = 'the collage [url=' . site_url() . "collage.php?id=$ThingID]" . display_str($Name) . '[/url]';
            $Subject = 'Collage Report: ' . display_str($Name);
        }
        break;
    case 'thread':
        $DB->query("
			SELECT Title
			FROM forums_topics
			WHERE ID = $ThingID");
        if (!$DB->has_results()) {
            $Error = Lang::get('reports', 'no_forum_thread_with_the_reported_id_found');
        } else {
            list($Title) = $DB->next_record();
            $TypeLink = 'the forum thread [url=' . site_url() . "forums.php?action=viewthread&amp;threadid=$ThingID]" . display_str($Title) . '[/url]';
            $Subject = 'Forum Thread Report: ' . display_str($Title);
        }
        break;
    case 'post':
        if (isset($LoggedUser['PostsPerPage'])) {
            $PerPage = $LoggedUser['PostsPerPage'];
        } else {
            $PerPage = POSTS_PER_PAGE;
        }
        $DB->query("
			SELECT
				p.ID,
				p.Body,
				p.TopicID,
				(
					SELECT COUNT(p2.ID)
					FROM forums_posts AS p2
					WHERE p2.TopicID = p.TopicID
						AND p2.ID <= p.ID
				) AS PostNum
			FROM forums_posts AS p
			WHERE p.ID = $ThingID");
        if (!$DB->has_results()) {
            $Error = Lang::get('reports', 'no_forum_post_with_the_reported_id_found');
        } else {
            list($PostID, $Body, $TopicID, $PostNum) = $DB->next_record();
            $TypeLink = 'this [url=' . site_url() . "forums.php?action=viewthread&amp;threadid=$TopicID&amp;post=$PostNum#post$PostID]forum post[/url]";
            $Subject = 'Forum Post Report: Post ID #' . display_str($PostID);
        }
        break;
    case 'comment':
        $DB->query("
			SELECT 1
			FROM comments
			WHERE ID = $ThingID");
        if (!$DB->has_results()) {
            $Error = Lang::get('reports', 'no_comment_with_the_reported_id_found');
        } else {
            $TypeLink = '[url=' . site_url() . "comments.php?action=jump&amp;postid=$ThingID]this comment[/url]";
            $Subject = 'Comment Report: ID #' . display_str($ThingID);
        }
        break;
    default:
        error(Lang::get('reports', 'incorrect_type'));
        break;
}
if (isset($Error)) {
    error($Error);
}

$DB->query("
	SELECT Reason
	FROM reports
	WHERE ID = $ReportID");
list($Reason) = $DB->next_record();

$Body = Lang::get('reports', 'you_reported_sth_for_the_reason_before') . "$TypeLink" . Lang::get('reports', 'you_reported_sth_for_the_reason_after') . ":\n[quote]{$Reason}[/quote]";

?>
<div class="LayoutBody">
    <div class="BodyHeader">
        <h2 class="BodyHeader-nav">
            <?= Lang::get('reports', 'send_a_message_to_before') ?><a href="user.php?id=<?= $ToID ?>"><?= $ComposeToUsername ?></a><?= Lang::get('reports', 'send_a_message_to_after') ?>
        </h2>
    </div>
    <form class="send_form" name="message" action="reports.php" method="post" id="messageform">
        <div class="BoxBody">
            <input type="hidden" name="action" value="takecompose" />
            <input type="hidden" name="toid" value="<?= $ToID ?>" />
            <input type="hidden" name="auth" value="<?= $LoggedUser['AuthKey'] ?>" />
            <div id="quickpost">
                <h3><?= Lang::get('reports', 'subject') ?></h3>
                <input class="Input" type="text" name="subject" size="95" value="<?= (!empty($Subject) ? $Subject : '') ?>" />
                <br />
                <h3><?= Lang::get('reports', 'body') ?></h3>
                <textarea class="Input" id="body" name="body" cols="95" rows="10"><?= (!empty($Body) ? $Body : '') ?></textarea>
            </div>
            <div id="preview" class="hidden"></div>
            <div id="buttons" class="center">
                <input class="Button" type="button" value="Preview" onclick="Quick_Preview();" />
                <input class="Button" type="submit" value="<?= Lang::get('inbox', 'send_message') ?>" />
            </div>
        </div>
    </form>
</div>
<?
View::show_footer();
?>