<?php

class PrecurioTableConstants {
	const ANALYTIC = 'analytics';
	const GROUPS = 'groups';
	const LOGS = 'logs';
	const PRIVILEGES = 'privileges';
	const RESOURCES = 'resources';
	const RULES = 'rules';
	const STATE_FIELDS = 'state_fields';
	const USERS = 'user_details';
	const AUTH = 'p2_users';
	const USER_GROUPS = 'user_groups';
	const WORKFLOW = 'workflow';
	const WORKFLOW_APPROVAL_ACCESS = 'workflow_approval_access';
	const WORKFLOW_REQUEST_ACCESS = 'workflow_request_access';
	const WORKFLOW_STATES = 'workflow_states';
	const NEWS = 'news';
	const CONTENT = 'content';
	const ARTICLES = 'articles';
	const ADVERTS = 'adverts';
	const LOCATIONS = 'locations';
	const DEPARTMENTS = 'department';
	const PROFILE_PICS = 'profile_pics';
	const OUT_OF_OFFICE = 'out_of_office';
	const ACTIVITY = 'user_activities';
	const STATUS_MESSAGES = 'status_messages';
	const COMMENTS = 'user_comments';
	const SHARED_CONTENTS = 'shared_contents';
	const EVENT = "events";
	const USER_EVENTS = "user_events";
	const EVENT_STATUS = "event_status";
	const RSS = "rss";
	const RSS_NEWS = 'rss_content';
	const EVENT_CONTENTS = 'event_contents';
	const CONTACTS = 'contacts';
	const FEATURED_USER = 'featured_user';
	const TASK = 'tasks';
	const TASK_USERS = 'task_users';
	const TASK_PROXY = 'task_proxy';
	const TASK_TRANSFERS = 'task_transfer';
	const TASK_GROUPS = 'task_groups';
	const POLL = "polls";
	const POLL_OPTIONS = "poll_options";
	const USER_VOTES = "votes_users";
	const LINKS = 'links';
	const NOTIFICATION_RULE = 'notification_setting';
	const GROUP_CONTENTS = "group_contents";
	const GROUP_SETTINGS = "group_settings";
	const CONTENT_APPROVAL = "content_approval";
	const FACTS = "facts";
	const CONTENT_RATINGS = "content_ratings";
	const MAIL_QUEUE = "mail_queue";
	const USER_SETTING = "user_settings";
	const USER_PROCESS = "user_processes";
	const ROLES = "roles";
	const DOCUMENTS = "documents";
	const NOTIFICATION_TYPE = "notification_type";
	const USER_SEARCH = "user_search";
	const BUGS  = "bugs";
	const USER_PROCESS_REJECT = "user_process_reject";
	const CATEGORYS = "categorys";
	const CONTENT_CATEGORYS = "content_categorys";
	const GROUP_CATEGORYS = "group_categorys";
	const APPOINTMENT = "appointment";
	const USER_APPOINTMENT = "user_appointment";
	const TELEPHONE_MESSAGES = "telephone_message";
	const VISITOR = "visitor";
	const VISITOR_APPOINTMENT = "visitor_appointment";
	const FORUMS = "forums";
	const FORUM_TOPICS = "forum_topics";
	const FORUM_POSTS = "forum_posts";
	
	public function toArray()
	{
		return get_defined_constants($this);
	}
}

?>