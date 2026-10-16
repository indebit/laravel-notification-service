<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Notification System API Documentation</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen">
    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
                    body .content .bash-example code { display: none; }
                    body .content .javascript-example code { display: none; }
            </style>

    <script>
        var tryItOutBaseUrl = "http://localhost:8000";
        var useCsrf = Boolean();
        var csrfUrl = "/sanctum/csrf-cookie";
    </script>
    <script src="{{ asset("/vendor/scribe/js/tryitout-5.9.0.js") }}"></script>

    <script src="{{ asset("/vendor/scribe/js/theme-default-5.9.0.js") }}"></script>

</head>

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">
    
            <div class="lang-selector">
                                            <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                    </div>
    
    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Search">
    </div>

    <div id="toc">
                    <ul id="tocify-header-introduction" class="tocify-header">
                <li class="tocify-item level-1" data-unique="introduction">
                    <a href="#introduction">Introduction</a>
                </li>
                            </ul>
                    <ul id="tocify-header-authenticating-requests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authenticating-requests">
                    <a href="#authenticating-requests">Authenticating requests</a>
                </li>
                            </ul>
                    <ul id="tocify-header-endpoints" class="tocify-header">
                <li class="tocify-item level-1" data-unique="endpoints">
                    <a href="#endpoints">Endpoints</a>
                </li>
                                    <ul id="tocify-subheader-endpoints" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="endpoints-POSTapi-test-broadcast">
                                <a href="#endpoints-POSTapi-test-broadcast">Debug-only: trigger a sample NotificationStatusChanged broadcast (see README).</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-notifications" class="tocify-header">
                <li class="tocify-item level-1" data-unique="notifications">
                    <a href="#notifications">Notifications</a>
                </li>
                                    <ul id="tocify-subheader-notifications" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="notifications-POSTapi-notifications">
                                <a href="#notifications-POSTapi-notifications">Create a notification</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="notifications-POSTapi-notifications-batch">
                                <a href="#notifications-POSTapi-notifications-batch">Create a batch of notifications</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="notifications-GETapi-notifications">
                                <a href="#notifications-GETapi-notifications">List notifications</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="notifications-GETapi-notifications-batch--batchId-">
                                <a href="#notifications-GETapi-notifications-batch--batchId-">Get notifications by batch ID</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="notifications-GETapi-notifications--id-">
                                <a href="#notifications-GETapi-notifications--id-">Get a notification by ID</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="notifications-PATCHapi-notifications--notification_id--cancel">
                                <a href="#notifications-PATCHapi-notifications--notification_id--cancel">Cancel a notification</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-observability" class="tocify-header">
                <li class="tocify-item level-1" data-unique="observability">
                    <a href="#observability">Observability</a>
                </li>
                                    <ul id="tocify-subheader-observability" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="observability-GETapi-metrics">
                                <a href="#observability-GETapi-metrics">Get queue and delivery metrics</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="observability-GETapi-health">
                                <a href="#observability-GETapi-health">Get service health status</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-templates" class="tocify-header">
                <li class="tocify-item level-1" data-unique="templates">
                    <a href="#templates">Templates</a>
                </li>
                                    <ul id="tocify-subheader-templates" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="templates-POSTapi-templates">
                                <a href="#templates-POSTapi-templates">Create template</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="templates-GETapi-templates">
                                <a href="#templates-GETapi-templates">List templates</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="templates-GETapi-templates--id-">
                                <a href="#templates-GETapi-templates--id-">Show template</a>
                            </li>
                                                                        </ul>
                            </ul>
            </div>

    <ul class="toc-footer" id="toc-footer">
                    <li style="padding-bottom: 5px;"><a href="{{ route("scribe.postman") }}">View Postman collection</a></li>
                            <li style="padding-bottom: 5px;"><a href="{{ route("scribe.openapi") }}">View OpenAPI spec</a></li>
                <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Last updated: May 1, 2026</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<p>Event-driven internal notification microservice for creating, queueing, delivering, and tracking SMS, email, and push notifications at scale.</p>
<aside>
    <strong>Base URL</strong>: <code>http://localhost:8000</code>
</aside>
<pre><code>This documentation aims to provide all the information you need to work with our API.

&lt;aside&gt;As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).&lt;/aside&gt;</code></pre>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<p>This API is not authenticated.</p>

        <h1 id="endpoints">Endpoints</h1>

    

                                <h2 id="endpoints-POSTapi-test-broadcast">Debug-only: trigger a sample NotificationStatusChanged broadcast (see README).</h2>

<p>
</p>



<span id="example-requests-POSTapi-test-broadcast">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/test/broadcast" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/test/broadcast"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-test-broadcast">
</span>
<span id="execution-results-POSTapi-test-broadcast" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-test-broadcast"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-test-broadcast"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-test-broadcast" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-test-broadcast">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-test-broadcast" data-method="POST"
      data-path="api/test/broadcast"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-test-broadcast', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-test-broadcast"
                    onclick="tryItOut('POSTapi-test-broadcast');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-test-broadcast"
                    onclick="cancelTryOut('POSTapi-test-broadcast');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-test-broadcast"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/test/broadcast</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-test-broadcast"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-test-broadcast"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                <h1 id="notifications">Notifications</h1>

    

                                <h2 id="notifications-POSTapi-notifications">Create a notification</h2>

<p>
</p>

<p>Creates a new notification and queues it for delivery. If an idempotency_key
is provided and matches an existing notification, the existing one is returned.</p>

<span id="example-requests-POSTapi-notifications">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/notifications" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"recipient\": \"+40745123456\",
    \"channel\": \"sms\",
    \"content\": \"Your order has shipped!\",
    \"priority\": \"high\",
    \"idempotency_key\": \"n\",
    \"scheduled_at\": \"2052-05-25\",
    \"template_name\": \"architecto\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/notifications"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "recipient": "+40745123456",
    "channel": "sms",
    "content": "Your order has shipped!",
    "priority": "high",
    "idempotency_key": "n",
    "scheduled_at": "2052-05-25",
    "template_name": "architecto"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-notifications">
            <blockquote>
            <p>Example response (201, Notification created):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;uuid&quot;,
        &quot;batch_id&quot;: null,
        &quot;channel&quot;: &quot;sms&quot;,
        &quot;recipient&quot;: &quot;+40745123456&quot;,
        &quot;content&quot;: &quot;Your order has shipped!&quot;,
        &quot;priority&quot;: &quot;high&quot;,
        &quot;status&quot;: &quot;pending&quot;,
        &quot;idempotency_key&quot;: &quot;order-123-shipped&quot;,
        &quot;processing_started_at&quot;: null,
        &quot;delivered_at&quot;: null,
        &quot;failed_at&quot;: null,
        &quot;attempt_count&quot;: 0,
        &quot;last_error&quot;: null,
        &quot;external_message_id&quot;: null,
        &quot;created_at&quot;: &quot;2026-04-29T10:00:00.000000Z&quot;,
        &quot;updated_at&quot;: &quot;2026-04-29T10:00:00.000000Z&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-notifications" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-notifications"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-notifications"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-notifications" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-notifications">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-notifications" data-method="POST"
      data-path="api/notifications"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-notifications', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-notifications"
                    onclick="tryItOut('POSTapi-notifications');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-notifications"
                    onclick="cancelTryOut('POSTapi-notifications');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-notifications"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/notifications</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-notifications"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-notifications"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>recipient</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="recipient"                data-endpoint="POSTapi-notifications"
               value="+40745123456"
               data-component="body">
    <br>
<p>The notification recipient (phone, email, or device token). Example: <code>+40745123456</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>channel</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="channel"                data-endpoint="POSTapi-notifications"
               value="sms"
               data-component="body">
    <br>
<p>The delivery channel. Example: <code>sms</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>content</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="content"                data-endpoint="POSTapi-notifications"
               value="Your order has shipped!"
               data-component="body">
    <br>
<p>The message content. Example: <code>Your order has shipped!</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>priority</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="priority"                data-endpoint="POSTapi-notifications"
               value="high"
               data-component="body">
    <br>
<p>The priority level. Defaults to normal. Example: <code>high</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>idempotency_key</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="idempotency_key"                data-endpoint="POSTapi-notifications"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>n</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>scheduled_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="scheduled_at"                data-endpoint="POSTapi-notifications"
               value="2052-05-25"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after or equal to <code>now</code>. Example: <code>2052-05-25</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>template_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="template_name"                data-endpoint="POSTapi-notifications"
               value="architecto"
               data-component="body">
    <br>
<p>The <code>name</code> of an existing record in the notification_templates table. Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>template_variables</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="template_variables"                data-endpoint="POSTapi-notifications"
               value=""
               data-component="body">
    <br>
<p>This field is required when <code>template_name</code> is present.</p>
        </div>
        </form>

                    <h2 id="notifications-POSTapi-notifications-batch">Create a batch of notifications</h2>

<p>
</p>

<p>Creates up to 1000 notifications in a single request, assigns a shared batch ID,
and queues each notification based on priority.</p>

<span id="example-requests-POSTapi-notifications-batch">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/notifications/batch" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"notifications\": [
        \"architecto\"
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/notifications/batch"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "notifications": [
        "architecto"
    ]
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-notifications-batch">
            <blockquote>
            <p>Example response (201, Batch created):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;batch_id&quot;: &quot;uuid&quot;,
    &quot;notifications&quot;: [
        {
            &quot;id&quot;: &quot;uuid&quot;,
            &quot;batch_id&quot;: &quot;uuid&quot;,
            &quot;channel&quot;: &quot;email&quot;,
            &quot;recipient&quot;: &quot;user@example.com&quot;,
            &quot;content&quot;: &quot;Flash sale starts now!&quot;,
            &quot;priority&quot;: &quot;normal&quot;,
            &quot;status&quot;: &quot;pending&quot;,
            &quot;idempotency_key&quot;: null,
            &quot;processing_started_at&quot;: null,
            &quot;delivered_at&quot;: null,
            &quot;failed_at&quot;: null,
            &quot;attempt_count&quot;: 0,
            &quot;last_error&quot;: null,
            &quot;external_message_id&quot;: null,
            &quot;created_at&quot;: &quot;2026-04-29T10:00:00.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2026-04-29T10:00:00.000000Z&quot;
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-notifications-batch" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-notifications-batch"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-notifications-batch"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-notifications-batch" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-notifications-batch">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-notifications-batch" data-method="POST"
      data-path="api/notifications/batch"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-notifications-batch', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-notifications-batch"
                    onclick="tryItOut('POSTapi-notifications-batch');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-notifications-batch"
                    onclick="cancelTryOut('POSTapi-notifications-batch');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-notifications-batch"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/notifications/batch</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-notifications-batch"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-notifications-batch"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>notifications</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Notification payloads (min 1, max 1000 items).</p>
            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>recipient</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="notifications.0.recipient"                data-endpoint="POSTapi-notifications-batch"
               value="user@example.com"
               data-component="body">
    <br>
<p>Recipient for each notification. Example: <code>user@example.com</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>channel</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="notifications.0.channel"                data-endpoint="POSTapi-notifications-batch"
               value="email"
               data-component="body">
    <br>
<p>Delivery channel for each notification. Example: <code>email</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>content</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="notifications.0.content"                data-endpoint="POSTapi-notifications-batch"
               value="Flash sale starts now!"
               data-component="body">
    <br>
<p>Message content. Example: <code>Flash sale starts now!</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>priority</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="notifications.0.priority"                data-endpoint="POSTapi-notifications-batch"
               value="low"
               data-component="body">
    <br>
<p>Optional priority. Defaults to normal. Example: <code>low</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>idempotency_key</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="notifications.0.idempotency_key"                data-endpoint="POSTapi-notifications-batch"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters. Example: <code>n</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>scheduled_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="notifications.0.scheduled_at"                data-endpoint="POSTapi-notifications-batch"
               value="2052-05-25"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after or equal to <code>now</code>. Example: <code>2052-05-25</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>template_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="notifications.0.template_name"                data-endpoint="POSTapi-notifications-batch"
               value="architecto"
               data-component="body">
    <br>
<p>The <code>name</code> of an existing record in the notification_templates table. Example: <code>architecto</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>template_variables</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="notifications.0.template_variables"                data-endpoint="POSTapi-notifications-batch"
               value=""
               data-component="body">
    <br>

                    </div>
                                    </details>
        </div>
        </form>

                    <h2 id="notifications-GETapi-notifications">List notifications</h2>

<p>
</p>

<p>Returns paginated notifications with optional filtering by status, channel,
date range, and batch ID.</p>

<span id="example-requests-GETapi-notifications">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/notifications?status=pending&amp;channel=sms&amp;from=2026-04-29T00%3A00%3A00%2B00%3A00&amp;to=2026-04-29T23%3A59%3A59%2B00%3A00&amp;per_page=15&amp;batch_id=d2c68f90-76d8-4c8e-9ad7-0f8b4a4cb9fe" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"status\": \"architecto\",
    \"channel\": \"architecto\",
    \"from\": \"2026-05-01T13:18:20\",
    \"to\": \"2052-05-24\",
    \"per_page\": 22,
    \"batch_id\": \"6b72fe4a-5b40-307c-bc24-f79acf9a1bb9\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/notifications"
);

const params = {
    "status": "pending",
    "channel": "sms",
    "from": "2026-04-29T00:00:00+00:00",
    "to": "2026-04-29T23:59:59+00:00",
    "per_page": "15",
    "batch_id": "d2c68f90-76d8-4c8e-9ad7-0f8b4a4cb9fe",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "status": "architecto",
    "channel": "architecto",
    "from": "2026-05-01T13:18:20",
    "to": "2052-05-24",
    "per_page": 22,
    "batch_id": "6b72fe4a-5b40-307c-bc24-f79acf9a1bb9"
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-notifications">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: &quot;uuid&quot;,
            &quot;channel&quot;: &quot;sms&quot;,
            &quot;recipient&quot;: &quot;+40745123456&quot;,
            &quot;content&quot;: &quot;Your order has shipped!&quot;,
            &quot;priority&quot;: &quot;high&quot;,
            &quot;status&quot;: &quot;pending&quot;,
            &quot;created_at&quot;: &quot;2026-04-29T10:00:00.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2026-04-29T10:00:00.000000Z&quot;
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;http://localhost:8000/api/notifications?page=1&quot;,
        &quot;last&quot;: &quot;http://localhost:8000/api/notifications?page=1&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: null
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;path&quot;: &quot;http://localhost:8000/api/notifications&quot;,
        &quot;per_page&quot;: 15,
        &quot;to&quot;: 1,
        &quot;total&quot;: 1
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-notifications" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-notifications"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-notifications"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-notifications" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-notifications">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-notifications" data-method="GET"
      data-path="api/notifications"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-notifications', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-notifications"
                    onclick="tryItOut('GETapi-notifications');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-notifications"
                    onclick="cancelTryOut('GETapi-notifications');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-notifications"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/notifications</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-notifications"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-notifications"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="GETapi-notifications"
               value="pending"
               data-component="query">
    <br>
<p>Filter by notification status. Example: <code>pending</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>channel</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="channel"                data-endpoint="GETapi-notifications"
               value="sms"
               data-component="query">
    <br>
<p>Filter by channel. Example: <code>sms</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>from</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="from"                data-endpoint="GETapi-notifications"
               value="2026-04-29T00:00:00+00:00"
               data-component="query">
    <br>
<p>ISO 8601 start date. Example: <code>2026-04-29T00:00:00+00:00</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>to</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="to"                data-endpoint="GETapi-notifications"
               value="2026-04-29T23:59:59+00:00"
               data-component="query">
    <br>
<p>ISO 8601 end date. Example: <code>2026-04-29T23:59:59+00:00</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-notifications"
               value="15"
               data-component="query">
    <br>
<p>Page size (1-100). Example: <code>15</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>batch_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="batch_id"                data-endpoint="GETapi-notifications"
               value="d2c68f90-76d8-4c8e-9ad7-0f8b4a4cb9fe"
               data-component="query">
    <br>
<p>Filter by batch UUID. Example: <code>d2c68f90-76d8-4c8e-9ad7-0f8b4a4cb9fe</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="GETapi-notifications"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>pending</code></li> <li><code>processing</code></li> <li><code>delivered</code></li> <li><code>failed</code></li> <li><code>cancelled</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>channel</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="channel"                data-endpoint="GETapi-notifications"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>sms</code></li> <li><code>email</code></li> <li><code>push</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>from</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="from"                data-endpoint="GETapi-notifications"
               value="2026-05-01T13:18:20"
               data-component="body">
    <br>
<p>Must be a valid date. Example: <code>2026-05-01T13:18:20</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>to</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="to"                data-endpoint="GETapi-notifications"
               value="2052-05-24"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after or equal to <code>from</code>. Example: <code>2052-05-24</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-notifications"
               value="22"
               data-component="body">
    <br>
<p>Must be at least 1. Must not be greater than 100. Example: <code>22</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>batch_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="batch_id"                data-endpoint="GETapi-notifications"
               value="6b72fe4a-5b40-307c-bc24-f79acf9a1bb9"
               data-component="body">
    <br>
<p>Must be a valid UUID. Example: <code>6b72fe4a-5b40-307c-bc24-f79acf9a1bb9</code></p>
        </div>
        </form>

                    <h2 id="notifications-GETapi-notifications-batch--batchId-">Get notifications by batch ID</h2>

<p>
</p>

<p>Returns paginated notifications belonging to the same batch.</p>

<span id="example-requests-GETapi-notifications-batch--batchId-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/notifications/batch/d2c68f90-76d8-4c8e-9ad7-0f8b4a4cb9fe" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/notifications/batch/d2c68f90-76d8-4c8e-9ad7-0f8b4a4cb9fe"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-notifications-batch--batchId-">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: &quot;uuid&quot;,
            &quot;batch_id&quot;: &quot;uuid&quot;,
            &quot;channel&quot;: &quot;email&quot;,
            &quot;recipient&quot;: &quot;user@example.com&quot;,
            &quot;content&quot;: &quot;Batch message&quot;,
            &quot;priority&quot;: &quot;normal&quot;,
            &quot;status&quot;: &quot;pending&quot;,
            &quot;idempotency_key&quot;: null,
            &quot;processing_started_at&quot;: null,
            &quot;delivered_at&quot;: null,
            &quot;failed_at&quot;: null,
            &quot;attempt_count&quot;: 0,
            &quot;last_error&quot;: null,
            &quot;external_message_id&quot;: null,
            &quot;created_at&quot;: &quot;2026-04-29T10:00:00.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2026-04-29T10:00:00.000000Z&quot;
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;http://localhost:8000/api/notifications/batch/d2c68f90-76d8-4c8e-9ad7-0f8b4a4cb9fe?page=1&quot;,
        &quot;last&quot;: &quot;http://localhost:8000/api/notifications/batch/d2c68f90-76d8-4c8e-9ad7-0f8b4a4cb9fe?page=1&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: null
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;path&quot;: &quot;http://localhost:8000/api/notifications/batch/d2c68f90-76d8-4c8e-9ad7-0f8b4a4cb9fe&quot;,
        &quot;per_page&quot;: 15,
        &quot;to&quot;: 1,
        &quot;total&quot;: 1
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-notifications-batch--batchId-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-notifications-batch--batchId-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-notifications-batch--batchId-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-notifications-batch--batchId-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-notifications-batch--batchId-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-notifications-batch--batchId-" data-method="GET"
      data-path="api/notifications/batch/{batchId}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-notifications-batch--batchId-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-notifications-batch--batchId-"
                    onclick="tryItOut('GETapi-notifications-batch--batchId-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-notifications-batch--batchId-"
                    onclick="cancelTryOut('GETapi-notifications-batch--batchId-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-notifications-batch--batchId-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/notifications/batch/{batchId}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-notifications-batch--batchId-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-notifications-batch--batchId-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>batchId</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="batchId"                data-endpoint="GETapi-notifications-batch--batchId-"
               value="d2c68f90-76d8-4c8e-9ad7-0f8b4a4cb9fe"
               data-component="url">
    <br>
<p>Batch UUID. Example: <code>d2c68f90-76d8-4c8e-9ad7-0f8b4a4cb9fe</code></p>
            </div>
                    </form>

                    <h2 id="notifications-GETapi-notifications--id-">Get a notification by ID</h2>

<p>
</p>

<p>Returns the notification details and delivery logs (if present).</p>

<span id="example-requests-GETapi-notifications--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/notifications/019ddd49-4700-70ec-a210-b83f311f1ca5" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/notifications/019ddd49-4700-70ec-a210-b83f311f1ca5"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-notifications--id-">
            <blockquote>
            <p>Example response (200, Notification found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;uuid&quot;,
        &quot;batch_id&quot;: null,
        &quot;channel&quot;: &quot;sms&quot;,
        &quot;recipient&quot;: &quot;+40745123456&quot;,
        &quot;content&quot;: &quot;Your order has shipped!&quot;,
        &quot;priority&quot;: &quot;high&quot;,
        &quot;status&quot;: &quot;delivered&quot;,
        &quot;idempotency_key&quot;: &quot;order-123-shipped&quot;,
        &quot;processing_started_at&quot;: &quot;2026-04-29T10:00:01.000000Z&quot;,
        &quot;delivered_at&quot;: &quot;2026-04-29T10:00:02.000000Z&quot;,
        &quot;failed_at&quot;: null,
        &quot;attempt_count&quot;: 1,
        &quot;last_error&quot;: null,
        &quot;external_message_id&quot;: &quot;provider-msg-123&quot;,
        &quot;created_at&quot;: &quot;2026-04-29T10:00:00.000000Z&quot;,
        &quot;updated_at&quot;: &quot;2026-04-29T10:00:02.000000Z&quot;,
        &quot;logs&quot;: [
            {
                &quot;id&quot;: 1,
                &quot;notification_id&quot;: &quot;uuid&quot;,
                &quot;attempt_number&quot;: 1,
                &quot;status&quot;: &quot;accepted&quot;,
                &quot;response_body&quot;: {
                    &quot;status&quot;: &quot;accepted&quot;
                },
                &quot;error_message&quot;: null,
                &quot;latency_ms&quot;: 120,
                &quot;created_at&quot;: &quot;2026-04-29T10:00:02.000000Z&quot;
            }
        ]
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-notifications--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-notifications--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-notifications--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-notifications--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-notifications--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-notifications--id-" data-method="GET"
      data-path="api/notifications/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-notifications--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-notifications--id-"
                    onclick="tryItOut('GETapi-notifications--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-notifications--id-"
                    onclick="cancelTryOut('GETapi-notifications--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-notifications--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/notifications/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-notifications--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-notifications--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="GETapi-notifications--id-"
               value="019ddd49-4700-70ec-a210-b83f311f1ca5"
               data-component="url">
    <br>
<p>The ID of the notification. Example: <code>019ddd49-4700-70ec-a210-b83f311f1ca5</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>notification</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="notification"                data-endpoint="GETapi-notifications--id-"
               value="019dd969-1008-7336-b2e4-00b27e14aa0a"
               data-component="url">
    <br>
<p>Notification UUID. Example: <code>019dd969-1008-7336-b2e4-00b27e14aa0a</code></p>
            </div>
                    </form>

                    <h2 id="notifications-PATCHapi-notifications--notification_id--cancel">Cancel a notification</h2>

<p>
</p>

<p>Cancels a notification if and only if the current status is pending.</p>

<span id="example-requests-PATCHapi-notifications--notification_id--cancel">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/notifications/019ddd49-4700-70ec-a210-b83f311f1ca5/cancel" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/notifications/019ddd49-4700-70ec-a210-b83f311f1ca5/cancel"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-notifications--notification_id--cancel">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;uuid&quot;,
        &quot;status&quot;: &quot;cancelled&quot;,
        &quot;updated_at&quot;: &quot;2026-04-29T10:15:00.000000Z&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;The given data was invalid.&quot;,
    &quot;errors&quot;: {
        &quot;notification&quot;: [
            &quot;Only pending notifications can be cancelled.&quot;
        ]
    }
}</code>
 </pre>
    </span>
<span id="execution-results-PATCHapi-notifications--notification_id--cancel" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-notifications--notification_id--cancel"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-notifications--notification_id--cancel"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-notifications--notification_id--cancel" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-notifications--notification_id--cancel">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-notifications--notification_id--cancel" data-method="PATCH"
      data-path="api/notifications/{notification_id}/cancel"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-notifications--notification_id--cancel', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-notifications--notification_id--cancel"
                    onclick="tryItOut('PATCHapi-notifications--notification_id--cancel');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-notifications--notification_id--cancel"
                    onclick="cancelTryOut('PATCHapi-notifications--notification_id--cancel');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-notifications--notification_id--cancel"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/notifications/{notification_id}/cancel</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-notifications--notification_id--cancel"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-notifications--notification_id--cancel"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>notification_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="notification_id"                data-endpoint="PATCHapi-notifications--notification_id--cancel"
               value="019ddd49-4700-70ec-a210-b83f311f1ca5"
               data-component="url">
    <br>
<p>The ID of the notification. Example: <code>019ddd49-4700-70ec-a210-b83f311f1ca5</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>notification</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="notification"                data-endpoint="PATCHapi-notifications--notification_id--cancel"
               value="019dd969-1008-7336-b2e4-00b27e14aa0a"
               data-component="url">
    <br>
<p>Notification UUID. Example: <code>019dd969-1008-7336-b2e4-00b27e14aa0a</code></p>
            </div>
                    </form>

                <h1 id="observability">Observability</h1>

    

                                <h2 id="observability-GETapi-metrics">Get queue and delivery metrics</h2>

<p>
</p>

<p>Returns queue depth, notification status counts, success rates, latency,
and throughput for the notification processing pipeline.</p>

<span id="example-requests-GETapi-metrics">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/metrics" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/metrics"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-metrics">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;queue_depth&quot;: {
        &quot;high&quot;: 0,
        &quot;default&quot;: 12,
        &quot;low&quot;: 45
    },
    &quot;notification_status_counts&quot;: {
        &quot;pending&quot;: 150,
        &quot;processing&quot;: 3,
        &quot;delivered&quot;: 8420,
        &quot;failed&quot;: 23,
        &quot;cancelled&quot;: 5
    },
    &quot;success_rate&quot;: {
        &quot;last_hour&quot;: {
            &quot;total&quot;: 500,
            &quot;delivered&quot;: 485,
            &quot;failed&quot;: 15,
            &quot;rate&quot;: 97
        },
        &quot;last_24h&quot;: {
            &quot;total&quot;: 12000,
            &quot;delivered&quot;: 11800,
            &quot;failed&quot;: 200,
            &quot;rate&quot;: 98.33
        }
    },
    &quot;latency&quot;: {
        &quot;average_ms&quot;: 120,
        &quot;p95_ms&quot;: 240,
        &quot;per_channel&quot;: {
            &quot;sms&quot;: 95,
            &quot;email&quot;: 140,
            &quot;push&quot;: 80
        }
    },
    &quot;throughput&quot;: {
        &quot;per_minute&quot;: 85.4,
        &quot;window_minutes&quot;: 5
    },
    &quot;timestamp&quot;: &quot;2026-04-29T14:00:00.000000Z&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-metrics" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-metrics"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-metrics"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-metrics" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-metrics">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-metrics" data-method="GET"
      data-path="api/metrics"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-metrics', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-metrics"
                    onclick="tryItOut('GETapi-metrics');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-metrics"
                    onclick="cancelTryOut('GETapi-metrics');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-metrics"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/metrics</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-metrics"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-metrics"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="observability-GETapi-health">Get service health status</h2>

<p>
</p>

<p>Checks database, Redis, and Horizon status for operational health.</p>

<span id="example-requests-GETapi-health">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/health" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/health"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-health">
            <blockquote>
            <p>Example response (200, Healthy):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;healthy&quot;,
    &quot;checks&quot;: {
        &quot;database&quot;: {
            &quot;status&quot;: &quot;ok&quot;,
            &quot;latency_ms&quot;: 2
        },
        &quot;redis&quot;: {
            &quot;status&quot;: &quot;ok&quot;,
            &quot;latency_ms&quot;: 1
        },
        &quot;horizon&quot;: {
            &quot;status&quot;: &quot;running&quot;
        }
    },
    &quot;timestamp&quot;: &quot;2026-04-29T14:00:00.000000Z&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (503, Unhealthy):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;unhealthy&quot;,
    &quot;checks&quot;: {
        &quot;database&quot;: {
            &quot;status&quot;: &quot;failed&quot;,
            &quot;latency_ms&quot;: 3,
            &quot;error&quot;: &quot;SQLSTATE[HY000] [2002] Connection refused&quot;
        },
        &quot;redis&quot;: {
            &quot;status&quot;: &quot;failed&quot;,
            &quot;latency_ms&quot;: 2,
            &quot;error&quot;: &quot;Connection refused&quot;
        },
        &quot;horizon&quot;: {
            &quot;status&quot;: &quot;inactive&quot;
        }
    },
    &quot;timestamp&quot;: &quot;2026-04-29T14:00:00.000000Z&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-health" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-health"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-health"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-health" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-health">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-health" data-method="GET"
      data-path="api/health"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-health', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-health"
                    onclick="tryItOut('GETapi-health');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-health"
                    onclick="cancelTryOut('GETapi-health');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-health"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/health</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-health"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-health"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                <h1 id="templates">Templates</h1>

    

                                <h2 id="templates-POSTapi-templates">Create template</h2>

<p>
</p>

<p>Creates a notification template for a specific channel.</p>

<span id="example-requests-POSTapi-templates">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/templates" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"order_shipped\",
    \"channel\": \"sms\",
    \"body\": \"Hi @{{name}}, your order {{order_id}} has shipped!\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/templates"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "order_shipped",
    "channel": "sms",
    "body": "Hi @{{name}}, your order {{order_id}} has shipped!"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-templates">
            <blockquote>
            <p>Example response (201):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;uuid&quot;,
        &quot;name&quot;: &quot;order_shipped&quot;,
        &quot;channel&quot;: &quot;sms&quot;,
        &quot;body&quot;: &quot;Hi {{name}}, your order {{order_id}} has shipped!&quot;,
        &quot;created_at&quot;: &quot;2026-04-29T10:00:00.000000Z&quot;,
        &quot;updated_at&quot;: &quot;2026-04-29T10:00:00.000000Z&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-templates" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-templates"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-templates"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-templates" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-templates">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-templates" data-method="POST"
      data-path="api/templates"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-templates', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-templates"
                    onclick="tryItOut('POSTapi-templates');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-templates"
                    onclick="cancelTryOut('POSTapi-templates');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-templates"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/templates</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-templates"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-templates"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-templates"
               value="order_shipped"
               data-component="body">
    <br>
<p>Template unique identifier. Example: <code>order_shipped</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>channel</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="channel"                data-endpoint="POSTapi-templates"
               value="sms"
               data-component="body">
    <br>
<p>Target channel. Example: <code>sms</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="body"                data-endpoint="POSTapi-templates"
               value="Hi @{{name}}, your order {{order_id}} has shipped!"
               data-component="body">
    <br>
<p>Template body with placeholders. Example: <code>Hi @{{name}}, your order {{order_id}} has shipped!</code></p>
        </div>
        </form>

                    <h2 id="templates-GETapi-templates">List templates</h2>

<p>
</p>

<p>Returns all notification templates.</p>

<span id="example-requests-GETapi-templates">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/templates" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/templates"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-templates">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-correlation-id: c9c238e7-d10f-4f40-b805-6015cffdbed7
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: &quot;019de072-a901-7257-bded-59c08110da46&quot;,
            &quot;name&quot;: &quot;product_on_sale&quot;,
            &quot;channel&quot;: &quot;push&quot;,
            &quot;body&quot;: &quot;{{product_name}} is now {{discount}}% OFF! Hurry, limited time offer!&quot;,
            &quot;created_at&quot;: &quot;2026-04-30T22:11:44+00:00&quot;,
            &quot;updated_at&quot;: &quot;2026-04-30T22:11:44+00:00&quot;
        },
        {
            &quot;id&quot;: &quot;019de072-a8f9-7120-bb70-25dd77eae15b&quot;,
            &quot;name&quot;: &quot;product_price_drop&quot;,
            &quot;channel&quot;: &quot;email&quot;,
            &quot;body&quot;: &quot;{{product_name}} is now {{discount}}% OFF! Hurry, limited time offer!&quot;,
            &quot;created_at&quot;: &quot;2026-04-30T22:11:43+00:00&quot;,
            &quot;updated_at&quot;: &quot;2026-04-30T22:11:43+00:00&quot;
        },
        {
            &quot;id&quot;: &quot;019de072-a8fd-739f-be3c-e9e955b8144a&quot;,
            &quot;name&quot;: &quot;welcome&quot;,
            &quot;channel&quot;: &quot;email&quot;,
            &quot;body&quot;: &quot;Welcome to Our Company, {{name}}! Your account is ready. Get started at {{dashboard_url}}.&quot;,
            &quot;created_at&quot;: &quot;2026-04-30T22:11:43+00:00&quot;,
            &quot;updated_at&quot;: &quot;2026-04-30T22:11:43+00:00&quot;
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;http://localhost:8000/api/templates?page=1&quot;,
        &quot;last&quot;: &quot;http://localhost:8000/api/templates?page=1&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: null
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;links&quot;: [
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;&amp;laquo; Previous&quot;,
                &quot;page&quot;: null,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;http://localhost:8000/api/templates?page=1&quot;,
                &quot;label&quot;: &quot;1&quot;,
                &quot;page&quot;: 1,
                &quot;active&quot;: true
            },
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;Next &amp;raquo;&quot;,
                &quot;page&quot;: null,
                &quot;active&quot;: false
            }
        ],
        &quot;path&quot;: &quot;http://localhost:8000/api/templates&quot;,
        &quot;per_page&quot;: 15,
        &quot;to&quot;: 3,
        &quot;total&quot;: 3
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-templates" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-templates"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-templates"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-templates" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-templates">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-templates" data-method="GET"
      data-path="api/templates"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-templates', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-templates"
                    onclick="tryItOut('GETapi-templates');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-templates"
                    onclick="cancelTryOut('GETapi-templates');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-templates"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/templates</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-templates"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-templates"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="templates-GETapi-templates--id-">Show template</h2>

<p>
</p>

<p>Returns a template by UUID.</p>

<span id="example-requests-GETapi-templates--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/templates/019de072-a8f9-7120-bb70-25dd77eae15b" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/templates/019de072-a8f9-7120-bb70-25dd77eae15b"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-templates--id-">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-correlation-id: e1854035-23ae-46e2-b2e1-afd95b2cac12
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: &quot;019de072-a8f9-7120-bb70-25dd77eae15b&quot;,
        &quot;name&quot;: &quot;product_price_drop&quot;,
        &quot;channel&quot;: &quot;email&quot;,
        &quot;body&quot;: &quot;{{product_name}} is now {{discount}}% OFF! Hurry, limited time offer!&quot;,
        &quot;created_at&quot;: &quot;2026-04-30T22:11:43+00:00&quot;,
        &quot;updated_at&quot;: &quot;2026-04-30T22:11:43+00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-templates--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-templates--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-templates--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-templates--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-templates--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-templates--id-" data-method="GET"
      data-path="api/templates/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-templates--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-templates--id-"
                    onclick="tryItOut('GETapi-templates--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-templates--id-"
                    onclick="cancelTryOut('GETapi-templates--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-templates--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/templates/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-templates--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-templates--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="GETapi-templates--id-"
               value="019de072-a8f9-7120-bb70-25dd77eae15b"
               data-component="url">
    <br>
<p>The ID of the template. Example: <code>019de072-a8f9-7120-bb70-25dd77eae15b</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>template</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="template"                data-endpoint="GETapi-templates--id-"
               value="019dd969-1008-7336-b2e4-00b27e14aa0a"
               data-component="url">
    <br>
<p>Template UUID. Example: <code>019dd969-1008-7336-b2e4-00b27e14aa0a</code></p>
            </div>
                    </form>

            

        
    </div>
    <div class="dark-box">
                    <div class="lang-selector">
                                                        <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                                        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                            </div>
            </div>
</div>
</body>
</html>
