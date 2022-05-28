<?php
Text::$TOC = true;

$NewsCount = 5;
if (!$News = $Cache->get_value('news')) {
    $ForumID = NEWS_FORUM_ID;
    $DB->query("SELECT `ID`, `Title`, `CreatedTime` , `IsSticky` FROM `forums_topics` WHERE `ForumID` = '$ForumID' AND  `IsNotice` = '1'  ORDER BY `IsSticky` DESC, `CreatedTime` DESC LIMIT $NewsCount");
    $topics = $DB->to_array(false, MYSQLI_NUM, false);
    $News = array();
    foreach ($topics as $key => $topic) {
        $ID = $topic[0];
        $Title = $topic[1];
        $DB->query("SELECT `Body` FROM `forums_posts` WHERE `TopicID` = '$ID'");
        $Bodys = $DB->to_array(false, MYSQLI_NUM, false);
        $Body = $Bodys[0][0];
        $Time = $topic[2];
        $IsSticky = $topic[3];
        $News[] = array($ID, $Title, $Body, $Time, $IsSticky);
    }
    $Cache->cache_value('news', $News, 3600 * 24 * 30);
    if (count($News) > 0) {
        $Cache->cache_value('news_latest_id', $News[0][0], 0);
        $Cache->cache_value('news_latest_title', $News[0][1], 0);
    }
}
if ($_SERVER['REQUEST_URI'] === '/index.php') {
    if (count($News) > 0 && $LoggedUser['LastReadNews'] != $News[0][0]) {
        $Cache->begin_transaction("user_info_heavy_$UserID");
        $Cache->update_row(false, array('LastReadNews' => $News[0][0]));
        $Cache->commit_transaction(0);
        $DB->query("
			UPDATE users_info
			SET LastReadNews = '" . $News[0][0] . "'
			WHERE UserID = $UserID");
        $LoggedUser['LastReadNews'] = $News[0][0];
    }
}

View::show_header(Lang::get('index', 'index'), 'comments', 'PageHome');
?>
<div class="LayoutMainSidebar">
    <div class="Sidebar LayoutMainSidebar-sidebar">
        <? include('month_movie.php'); ?>

        <?
        if (($TopicID = $Cache->get_value('polls_featured')) === false) {
            $DB->query("
		SELECT TopicID
		FROM forums_polls
		ORDER BY Featured DESC
		LIMIT 1");
            list($TopicID) = $DB->next_record();
            $Cache->cache_value('polls_featured', $TopicID, 0);
        }
        if ($TopicID) {
            if (($Poll = $Cache->get_value("polls_$TopicID")) === false) {
                $DB->query("
			SELECT Question, Answers, Featured, Closed, MaxCount
			FROM forums_polls
			WHERE TopicID = '$TopicID'");
                list($Question, $Answers, $Featured, $Closed, $MaxCount) = $DB->next_record(MYSQLI_NUM, array(1));
                $Answers = unserialize($Answers);
                $DB->query("
			SELECT Vote, COUNT(UserID)
			FROM forums_polls_votes
			WHERE TopicID = '$TopicID'
				AND Vote != '0'
			GROUP BY Vote");
                $VoteArray = $DB->to_array(false, MYSQLI_NUM);

                $Votes = array();
                foreach ($VoteArray as $VoteSet) {
                    list($Key, $Value) = $VoteSet;
                    $Votes[$Key] = $Value;
                }

                for ($i = 1, $il = count($Answers); $i <= $il; ++$i) {
                    if (!isset($Votes[$i])) {
                        $Votes[$i] = 0;
                    }
                }
                $Cache->cache_value("polls_$TopicID", array($Question, $Answers, $Votes, $Featured, $Closed, $MaxCount), 0);
            } else {
                list($Question, $Answers, $Votes, $Featured, $Closed, $MaxCount) = $Poll;
            }
            if (!$Closed) {
                if (!empty($Votes)) {
                    $TotalVotes = array_sum($Votes);
                    $MaxVotes = max($Votes);
                    $DB->query("SELECT count(distinct `UserID`) FROM `forums_polls_votes` WHERE `TopicID`='$TopicID' and vote!=0");
                    list($PeopleCount) = $DB->next_record();
                } else {
                    $TotalVotes = 0;
                    $MaxVotes = 0;
                    $PeopleCount = 0;
                }

                $DB->query("
		SELECT Vote
		FROM forums_polls_votes
		WHERE UserID = '" . $LoggedUser['ID'] . "'
			AND TopicID = '$TopicID'");
                $UserResponses = $DB->to_array();
                $BlankVote = false;
                foreach ($UserResponses as $UserResponse) {
                    $UserResponse = $UserResponse[0];
                    if (!empty($UserResponse) && $UserResponse != 0) {
                        $Answers[$UserResponse] = '&raquo; ' . $Answers[$UserResponse];
                    } else {
                        if (!empty($UserResponse) && $RevealVoters) {
                            $Answers[$UserResponse] = '&raquo; ' . $Answers[$UserResponse];
                        }
                    }
                    if (!$UserResponse) {
                        $BlankVote = true;
                    }
                }
                $PollStatus = $Closed ? ' [' . Lang::get('forums', 'closed') . ']' : ''
        ?>
                <div class="SidebarItemPoll SidebarItem Box is-limitHeight">
                    <div class="SidebarItem-header Box-header">
                        <?= Lang::get('index', 'poll') ?><?= $PollStatus ?>
                    </div>
                    <div class="SidebarItem-body Box-body">
                        <div class="Poll">
                            <a class="Poll-question" href="forums.php?action=viewthread&amp;threadid=<?= $TopicID ?>">
                                <?= display_str($Question) . " (" . Lang::get('forums', 'limited1') . " $MaxCount " . Lang::get('forums', 'limited2') . ")" ?>
                            </a>
                            <? if ($UserResponse !== null || $Closed) { ?>
                                <ul class="Poll-answers">
                                    <? foreach ($Answers as $i => $Answer) {
                                        if ($TotalVotes > 0) {
                                            $Ratio = $Votes[$i] / $MaxVotes;
                                            $Percent = $Votes[$i] / $TotalVotes;
                                        } else {
                                            $Ratio = 0;
                                            $Percent = 0;
                                        }
                                    ?>
                                        <li class="Poll-answerItem">
                                            <div class="Poll-answerText"><?= display_str($Answers[$i]) ?> (<?= $Votes[$i] . ", " . number_format($Percent * 100, 2) ?>%)</div>
                                            <progress class="Progress" value="<?= $Ratio ?>"></progress>
                                        </li>
                                    <? } ?>
                                </ul>
                                <div class="Poll-count">
                                    <?= Lang::get('forums', 'votes') ?>: <?= number_format($TotalVotes) ?>, <?= Lang::get('forums', 'voters') ?>: <?= $PeopleCount ?>
                                </div>
                            <?  } else { ?>
                                <div id="PollContainer">
                                    <form class="Poll-voteForm" name="poll" id="poll" action="">
                                        <input type="hidden" name="action" value="poll" />
                                        <input type="hidden" name="auth" value="<?= $LoggedUser['AuthKey'] ?>" />
                                        <input type="hidden" name="topicid" value="<?= $TopicID ?>" />
                                        <ul class="Poll-answers">
                                            <? foreach ($Answers as $i => $Answer) { ?>
                                                <li class="Poll-answerItem Checkbox">
                                                    <input class="Input" type="checkbox" name="vote[]" id="answer_<?= $i ?>" value="<?= $i ?>" onclick="PollCount(<?= $MaxCount ?>)" />
                                                    <label class="Checkbox-label" for="answer_<?= $i ?>">
                                                        <?= display_str($Answers[$i]) ?>
                                                    </label>
                                                </li>
                                            <? } ?>
                                            <li class="Poll-answerItem Checkbox">
                                                <input class="Input" type="checkbox" name="vote[]" id="answer_0" value="0" onclick="PollCount(0)" />
                                                <label class="Checkbox-label" for="answer_0"><?= Lang::get('index', 'blank') ?></label>
                                            </li>
                                        </ul>
                                        <input class="Poll-voteFormSubmit Button" type="button" onclick="ajax.post('index.php', 'poll', function(response) { $('#PollContainer').raw().innerHTML = response } );" value='<?= Lang::get('index', 'vote') ?>' />
                                    </form>
                                </div>
                            <?  } ?>
                        </div>
                    </div>
                </div>
            <? } ?>
        <?  } ?>

        <?
        $LatestThread = Forums::latest_thread();
        if ($LatestThread) {
        ?>
            <div class="SidebarItem Box">
                <div class="SidebarItem-header Box-header">
                    <a href="forum.php"><?= Lang::get('index', 'latest_thread') ?></a>
                </div>
                <ul class="SidebarItem-body Box-body SidebarList is-ordered">
                    <?
                    foreach ($LatestThread as $Thread) {
                        $Title = $Thread['Title'];
                        $ThreadID = $Thread['ID'];
                        $PostID = $Thread['LastPostID'];
                    ?>
                        <li class="SidebarList-item">
                            <a href="forums.php?action=viewthread&threadid=<?= $ThreadID ?>&postid=<?= $PostID ?>#post<?= $PostID ?>"><?= $Title ?></a>
                        </li>
                    <?
                    }
                    ?>
                </ul>
            </div>
        <?  } ?>

        <? if (check_perms('users_mod')) { ?>
            <div class="SidebarItemStaffBlog SidebarItem Box">
                <div class="SidebarItem-header Box-header">
                    <a href="staffblog.php"><?= Lang::get('index', 'staff_note') ?></a>
                </div>
                <?
                if (($Blog = $Cache->get_value('staff_blog')) === false) {
                    $DB->query("
		SELECT
			b.ID,
			um.Username,
			b.Title,
			b.Body,
			b.Time
		FROM staff_blog AS b
			LEFT JOIN users_main AS um ON b.UserID = um.ID
		ORDER BY Time DESC");
                    $Blog = $DB->to_array(false, MYSQLI_NUM);
                    $Cache->cache_value('staff_blog', $Blog, 1209600);
                }
                if (($SBlogReadTime = $Cache->get_value('staff_blog_read_' . $LoggedUser['ID'])) === false) {
                    $DB->query("
		SELECT Time
		FROM staff_blog_visits
		WHERE UserID = " . $LoggedUser['ID']);
                    if (list($SBlogReadTime) = $DB->next_record()) {
                        $SBlogReadTime = strtotime($SBlogReadTime);
                    } else {
                        $SBlogReadTime = 0;
                    }
                    $Cache->cache_value('staff_blog_read_' . $LoggedUser['ID'], $SBlogReadTime, 1209600);
                }
                ?>
                <ul class="SidebarItem-body Box-body SidebarList is-ordered">
                    <?
                    $End = min(count($Blog), 5);
                    for ($i = 0; $i < $End; $i++) {
                        list($BlogID, $Author, $Title, $Body, $BlogTime) = $Blog[$i];
                        $BlogTime = strtotime($BlogTime);
                    ?>
                        <li class="SidebarList-item">
                            <?= $SBlogReadTime < $BlogTime ? '<strong>' : '' ?>
                            <a href="staffblog.php#blog<?= $BlogID ?>"><?= $Title ?></a>
                            <?= $SBlogReadTime < $BlogTime ? '</strong>' : '' ?>
                        </li>
                    <?
                    }
                    ?>
                </ul>
            </div>
        <?  } ?>
        <div class="SidbarItemBlog SidebarItem Box">
            <div class="SidebarItem-header Box-header">
                <a href="blog.php"><?= Lang::get('index', 'blog_note') ?></a>
            </div>
            <?
            if (($Blog = $Cache->get_value('blog')) === false) {
                $DB->query("
		SELECT
			b.ID,
			um.Username,
			b.UserID,
			b.Title,
			b.Body,
			b.Time,
			b.ThreadID
		FROM blog AS b
			LEFT JOIN users_main AS um ON b.UserID = um.ID
		ORDER BY Time DESC
		LIMIT 20");
                $Blog = $DB->to_array();
                $Cache->cache_value('blog', $Blog, 1209600);
            }
            ?>
            <ul class="SidebarItem-body Box-body SidebarList is-ordered">
                <?
                if (count($Blog) < 5) {
                    $Limit = count($Blog);
                } else {
                    $Limit = 5;
                }
                for ($i = 0; $i < $Limit; $i++) {
                    list($BlogID, $Author, $AuthorID, $Title, $Body, $BlogTime, $ThreadID) = $Blog[$i];
                ?>
                    <li class="SidebarList-item">
                        <a href="blog.php#blog<?= $BlogID ?>"><?= $Title ?></a>
                    </li>
                <?
                }
                ?>
            </ul>
        </div>

        <?
        include('contest_leaderboard.php');
        if (ENABLE_SITEHISTORY) {
            SiteHistoryView::render_recent_sidebar(SiteHistory::get_events(null, null, null, null, null, null, 5));
        }
        ?>


        <div class="SidebarItemStats SidebarItem Box">
            <div class="SidebarItem-header Box-header">
                <?= Lang::get('index', 'stats') ?>
            </div>
            <ul class="SidebarItem-body Box-body SidebarList">
                <? if (USER_LIMIT > 0) { ?>
                    <li><?= Lang::get('index', 'user_limit') ?>: <?= number_format(USER_LIMIT) ?></li>
                <?
                }

                if (($UserCount = $Cache->get_value('stats_user_count')) === false) {
                    $DB->query("
		SELECT COUNT(ID)
		FROM users_main
		WHERE Enabled = '1'");
                    list($UserCount) = $DB->next_record();
                    $Cache->cache_value('stats_user_count', $UserCount, 3600 * 24); //inf cache
                }
                $UserCount = (int)$UserCount;
                ?>
                <li class="SidebarList-item"><?= Lang::get('index', 'enable_users') ?>: <?= number_format($UserCount) ?>&nbsp;<a href="stats.php?action=users" class="brackets"><?= Lang::get('index', 'details') ?></a></li>
                <?

                if (($UserStats = $Cache->get_value('stats_users')) === false) {
                    $DB->query("
		SELECT COUNT(ID)
		FROM users_main
		WHERE Enabled = '1'
			AND LastAccess > '" . time_minus(3600 * 24) . "'");
                    list($UserStats['Day']) = $DB->next_record();

                    $DB->query("
		SELECT COUNT(ID)
		FROM users_main
		WHERE Enabled = '1'
			AND LastAccess > '" . time_minus(3600 * 24 * 7) . "'");
                    list($UserStats['Week']) = $DB->next_record();

                    $DB->query("
		SELECT COUNT(ID)
		FROM users_main
		WHERE Enabled = '1'
			AND LastAccess > '" . time_minus(3600 * 24 * 30) . "'");
                    list($UserStats['Month']) = $DB->next_record();

                    $Cache->cache_value('stats_users', $UserStats, 0);
                }
                ?>
                <li class="SidebarList-item"><?= Lang::get('index', 'day_visit') ?>: <?= number_format($UserStats['Day']) ?> (<?= number_format($UserStats['Day'] / $UserCount * 100, 2) ?>%)</li>
                <li class="SidebarList-item"><?= Lang::get('index', 'wek_visit') ?>: <?= number_format($UserStats['Week']) ?> (<?= number_format($UserStats['Week'] / $UserCount * 100, 2) ?>%)</li>
                <li class="SidebarList-item"><?= Lang::get('index', 'mon_visit') ?>: <?= number_format($UserStats['Month']) ?> (<?= number_format($UserStats['Month'] / $UserCount * 100, 2) ?>%)</li>
                <?

                if (($TorrentCount = $Cache->get_value('stats_torrent_count')) === false) {
                    $DB->query("
		SELECT COUNT(ID)
		FROM torrents");
                    list($TorrentCount) = $DB->next_record();
                    $Cache->cache_value('stats_torrent_count', $TorrentCount, 604800); // staggered 1 week cache
                }

                if (($MoviesCount = $Cache->get_value('stats_album_count')) === false) {
                    $DB->query("
		SELECT COUNT(ID)
		FROM torrents_group
		WHERE CategoryID = '1'");
                    list($MoviesCount) = $DB->next_record();
                    $Cache->cache_value('stats_album_count', $MoviesCount, 86400); // staggered 1 day cache
                }

                if (($DramaCount = $Cache->get_value('stats_drama_count')) === false) {
                    $DB->query("
		SELECT COUNT(ID)
		FROM torrents_group
		WHERE CategoryID = '2'");
                    list($DramaCount) = $DB->next_record();
                    $Cache->cache_value('stats_drama_count', $DramaCount, 604830); // staggered 1 week cache
                }

                if (($ArtistCount = $Cache->get_value('stats_artist_count')) === false) {
                    $DB->query("
		SELECT COUNT(ArtistID)
		FROM artists_group");
                    list($ArtistCount) = $DB->next_record();
                    $Cache->cache_value('stats_artist_count', $ArtistCount, 604860); // staggered 1 week cache
                }
                ?>
                <li class="SidebarList-item"><?= Lang::get('global', 'torrents') ?>: <?= number_format($TorrentCount) ?></li>
                <li class="SidebarList-item"><?= Lang::get('index', 'moviegroups') ?>: <?= number_format($MoviesCount) ?></li>
                <li class="SidebarList-item"><?= Lang::get('global', 'artist') ?>: <?= number_format($ArtistCount) ?></li>
                <?
                //End Torrent Stats

                if (($CollageCount = $Cache->get_value('stats_collages')) === false) {
                    $DB->query("
		SELECT COUNT(ID)
		FROM collages");
                    list($CollageCount) = $DB->next_record();
                    $Cache->cache_value('stats_collages', $CollageCount, 11280); //staggered 1 week cache
                }
                if (ENABLE_COLLAGES) {
                ?>
                    <li class="SidebarList-item"><?= Lang::get('index', 'collage') ?>: <?= number_format($CollageCount) ?></li>
                <?
                }

                if (($RequestStats = $Cache->get_value('stats_requests')) === false) {
                    $DB->query("
		SELECT COUNT(ID)
		FROM requests");
                    list($RequestCount) = $DB->next_record();
                    $DB->query("
		SELECT COUNT(ID)
		FROM requests
		WHERE FillerID > 0");
                    list($FilledCount) = $DB->next_record();
                    $Cache->cache_value('stats_requests', array($RequestCount, $FilledCount), 11280);
                } else {
                    list($RequestCount, $FilledCount) = $RequestStats;
                }
                $RequestPercentage = $RequestCount > 0 ? $FilledCount / $RequestCount * 100 : 0;
                ?>
                <li class="SidebarList-item"><?= Lang::get('global', 'requests') ?>: <?= number_format($RequestCount) ?> (<?= number_format($RequestPercentage, 2) ?>% <?= Lang::get('index', 'filled') ?>)</li>
                <?

                if ($SnatchStats = $Cache->get_value('stats_snatches')) {
                ?>
                    <li class="SidebarList-item"><?= Lang::get('index', 'snatches') ?>: <?= number_format($SnatchStats) ?></li>
                <?
                }

                if (($PeerStats = $Cache->get_value('stats_peers')) === false) {
                    //Cache lock!
                    $PeerStatsLocked = $Cache->get_value('stats_peers_lock');
                    if (!$PeerStatsLocked) {
                        $Cache->cache_value('stats_peers_lock', 1, 30);
                        $DB->query("
			SELECT IF(remaining=0,'Seeding','Leeching') AS Type, COUNT(uid)
			FROM xbt_files_users
			WHERE active = 1
			GROUP BY Type");
                        $PeerCount = $DB->to_array(0, MYSQLI_NUM, false);
                        $SeederCount = $PeerCount['Seeding'][1] ?: 0;
                        $LeecherCount = $PeerCount['Leeching'][1] ?: 0;
                        $Cache->cache_value('stats_peers', array($LeecherCount, $SeederCount), 1209600); // 2 week cache
                        $Cache->delete_value('stats_peers_lock');
                    }
                } else {
                    $PeerStatsLocked = false;
                    list($LeecherCount, $SeederCount) = $PeerStats;
                }

                if (!$PeerStatsLocked) {
                    $Ratio = Format::get_ratio_html($SeederCount, $LeecherCount);
                    $PeerCount = number_format($SeederCount + $LeecherCount);
                    $SeederCount = number_format($SeederCount);
                    $LeecherCount = number_format($LeecherCount);
                } else {
                    $PeerCount = $SeederCount = $LeecherCount = $Ratio = 'Server busy';
                }
                ?>
                <li class="SidebarList-item"><?= Lang::get('index', 'peers') ?>: <?= $PeerCount ?></li>
                <li class="SidebarList-item"><?= Lang::get('global', 'seeders') ?>: <?= $SeederCount ?></li>
                <li class="SidebarList-item"><?= Lang::get('global', 'leechers') ?>: <?= $LeecherCount ?></li>
                <li><?= Lang::get('index', 's_l_ratio') ?>: <?= $Ratio ?></li>
            </ul>
        </div>

    </div>


    <div class="LayoutMainSidebar-main">
        <div class="PostList PostListNews">
            <?
            $Count = 0;
            foreach ($News as $NewsItem) {
                list($NewsID, $Title, $Body, $NewsTime, $IsSticky) = $NewsItem;
                if (strtotime($NewsTime) > time()) {
                    continue;
                }
            ?>
                <div id="news<?= $NewsID ?>" class="Post Box news_post">
                    <div class="Post-header Box-header">
                        <div class="Post-headerLeft">
                            <span class="Post-headerTitle"><?= Text::full_format($Title) ?></span>
                            <?= time_diff($NewsTime); ?>
                        </div>
                        <div class="Post-headerActions">
                            <a href="forums.php?action=viewthread&amp;threadid=<?= $NewsID ?>" class="brackets"><?= Lang::get('index', 'discuss') ?></a>
                            <? if (check_perms('admin_manage_news')) { ?>
                                - <a href="forums.php?action=viewthread&amp;threadid=<?= $NewsID ?>" class="brackets"><?= Lang::get('global', 'edit') ?></a>
                            <?  } ?>
                            - <a class="Post-toggleButton <?= $IsSticky ? 'is-sticky' : '' ?>" href="#" onclick="$('#newsbody<?= $NewsID ?>').gtoggle(); this.innerHTML = (this.innerHTML == '<?= Lang::get('global', 'hide') ?>' ? '<?= Lang::get('global', 'show') ?>' : '<?= Lang::get('global', 'hide') ?>'); return false;" class="brackets"><?= $IsSticky ? Lang::get('index', 'show') : Lang::get('index', 'hide')  ?></a>
                        </div>
                    </div>
                    <div id="newsbody<?= $NewsID ?>" class="HtmlText PostArticle Post-body Box-body <?= $IsSticky ? 'hidden' : '' ?>"><?= Text::full_format($Body) ?></div>
                </div>
            <?
                if (++$Count > ($NewsCount - 1)) {
                    break;
                }
            }
            ?>
            <div id="more_news">
                <div>
                    <em>
                        <!--
                        <span>
                            <a href="#" onclick="news_ajax(event, 3, <?= $NewsCount ?>, <?= check_perms('admin_manage_news') ? 1 : 0; ?>, false); return false;"><?= Lang::get('index', 'add_more') ?></a>
                            <?= Lang::get('index', 'period') ?>
                        </span>
                        -->
                        <?= Lang::get('index', 'add_old') ?>
                        <a href="forums.php?action=viewforum&amp;forumid=7"><?= Lang::get('index', 'click_here') ?></a>
                        <?= Lang::get('index', 'period') ?>
                    </em>
                </div>
            </div>
        </div>
    </div>
</div>
<?
View::show_footer(array('disclaimer' => true), 'home.js');

function contest() {
    global $DB, $Cache, $LoggedUser;

    list($Contest, $TotalPoints) = $Cache->get_value('contest');
    if (!$Contest) {
        $DB->query("
			SELECT
				UserID,
				SUM(Points),
				Username
			FROM users_points AS up
				JOIN users_main AS um ON um.ID = up.UserID
			GROUP BY UserID
			ORDER BY SUM(Points) DESC
			LIMIT 20");
        $Contest = $DB->to_array();

        $DB->query("
			SELECT SUM(Points)
			FROM users_points");
        list($TotalPoints) = $DB->next_record();

        $Cache->cache_value('contest', array($Contest, $TotalPoints), 600);
    }

?>
    <!-- Contest Section -->
    <div class="box box_contest">
        <div class="head colhead_dark"><strong>Quality time scoreboard</strong></div>
        <div class="pad">
            <ol>
                <?
                foreach ($Contest as $User) {
                    list($UserID, $Points, $Username) = $User;
                ?>
                    <li><?= Users::format_username($UserID, false, false, false) ?> (<?= number_format($Points) ?>)</li>
                <?  } ?>
            </ol>
            Total uploads: <?= $TotalPoints ?><br />
            <a href="index.php?action=scoreboard">Full scoreboard</a>
        </div>
    </div>
    <!-- END contest Section -->
<?
} // contest()
?>