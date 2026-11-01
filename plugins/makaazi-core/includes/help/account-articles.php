<?php
/**
 * Help articles for agents and property owners, shown in the Help tab of [makaazi_dashboard].
 *
 * Tokens: {url:submit}  {url:listings}  {url:enquiries-tab}  {url:profile}  {help:ARTICLE_ID}.
 * Change them with the `makaazi_help_articles` filter (context "account").
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'id'       => 'acc-start',
		'group'    => __( 'Getting started', 'makaazi' ),
		'title'    => __( 'How listing works', 'makaazi' ),
		'summary'  => __( 'From your first draft to a live listing.', 'makaazi' ),
		'keywords' => 'start how list property steps',
		'body'     => __(
			'<ol>
<li>Open <a href="{url:submit}">Add property</a> and go through the five steps: Basics, Details, Photos, Location, Review.</li>
<li>Click <strong>Save draft</strong> at any time – nothing is public until you submit.</li>
<li>On the last step click <strong>Submit for review</strong>. Our team checks the listing, usually within one working day.</li>
<li>You get an email when it is live, or when something needs changing.</li>
</ol>
<p>Your listings, with their status and views, are on <a href="{url:listings}">My listings</a>.</p>',
			'makaazi'
		),
	),
	array(
		'id'       => 'acc-status',
		'group'    => __( 'Getting started', 'makaazi' ),
		'title'    => __( 'What each status means', 'makaazi' ),
		'summary'  => __( 'Draft, Pending review, Needs changes, Live, Sold and Let.', 'makaazi' ),
		'keywords' => 'status draft pending live needs changes sold let',
		'body'     => __(
			'<ul>
<li><strong>Draft</strong> – only you can see it. Finish it and submit when ready.</li>
<li><strong>Pending review</strong> – waiting for our team. You can still open it, but changes wait for the review.</li>
<li><strong>Needs changes</strong> – our team sent it back. The reason is shown on the listing and in your email. Click <strong>Fix and resubmit</strong>.</li>
<li><strong>Live</strong> – visible to everyone and in search. The eye icon shows how many times it has been viewed.</li>
<li><strong>Sold / Let</strong> – still online with a ribbon, but no longer in search. Click <strong>Relist</strong> if it becomes available again.</li>
</ul>',
			'makaazi'
		),
	),
	array(
		'id'       => 'acc-photos',
		'group'    => __( 'Your listing', 'makaazi' ),
		'title'    => __( 'Taking good photos', 'makaazi' ),
		'summary'  => __( 'Listings with clear photos get far more enquiries.', 'makaazi' ),
		'keywords' => 'photos pictures camera cover upload size',
		'body'     => __(
			'<ul>
<li>Hold your phone <strong>sideways</strong> (landscape) and keep it level.</li>
<li>Shoot in daylight with curtains open and lights on. Tidy each room first.</li>
<li>Include every room and at least one photo of the outside, the view and the parking.</li>
<li>Add at least 3 photos; 10–20 is better. JPG, PNG or WebP up to 8 MB each – large photos are made smaller before upload.</li>
<li>The first photo is the cover. Use <strong>Make cover photo</strong> to change it, and drag to reorder.</li>
</ul>',
			'makaazi'
		),
	),
	array(
		'id'       => 'acc-approval',
		'group'    => __( 'Your listing', 'makaazi' ),
		'title'    => __( 'Getting approved first time', 'makaazi' ),
		'summary'  => __( 'What our team checks before a listing goes live.', 'makaazi' ),
		'keywords' => 'approval review rejected tips title price description location',
		'body'     => __(
			'<ul>
<li><strong>Title</strong> says what and where, e.g. “3 Bedroom Apartment, Kilimani”.</li>
<li><strong>Price</strong> is the real asking price, and for rentals the period (per month, per year…).</li>
<li><strong>Description</strong> covers size, rooms, condition, what is included and what is nearby.</li>
<li><strong>Location</strong> uses a nearby road or landmark, not the house number. The pin stays private: visitors only see a shaded area.</li>
<li><strong>Photos</strong> are your own, clear and show the actual property.</li>
</ul>',
			'makaazi'
		),
	),
	array(
		'id'       => 'acc-manage',
		'group'    => __( 'Your listing', 'makaazi' ),
		'title'    => __( 'Edit, mark sold or let, and delete', 'makaazi' ),
		'summary'  => __( 'Keep your listings up to date.', 'makaazi' ),
		'keywords' => 'edit update sold let relist delete remove',
		'body'     => __(
			'<ul>
<li><strong>Edit</strong> – change anything and save. On some sites edits to a live listing are checked again before they show.</li>
<li><strong>Mark sold / Mark let</strong> – use this as soon as the deal is done so people stop calling about it.</li>
<li><strong>Relist</strong> – makes a sold or let listing available again.</li>
<li><strong>Delete</strong> – tap twice to confirm. This cannot be undone; marking as sold or let is usually better.</li>
</ul>',
			'makaazi'
		),
	),
	array(
		'id'       => 'acc-enquiries',
		'group'    => __( 'Enquiries & account', 'makaazi' ),
		'title'    => __( 'Answering enquiries', 'makaazi' ),
		'summary'  => __( 'Where messages from buyers and tenants appear.', 'makaazi' ),
		'keywords' => 'enquiries messages leads reply call whatsapp email',
		'body'     => __(
			'<p>Messages about your listings are emailed to you and listed on <a href="{url:enquiries-tab}">Enquiries</a>, with the person’s name, phone and email.</p>
<ul>
<li>Reply quickly – the first person to answer usually gets the deal.</li>
<li>Use the call, WhatsApp or email buttons next to each enquiry.</li>
<li>Never send a deposit or ID details to someone you have not met. Report anything suspicious to us.</li>
</ul>',
			'makaazi'
		),
	),
	array(
		'id'       => 'acc-profile',
		'group'    => __( 'Enquiries & account', 'makaazi' ),
		'title'    => __( 'Your profile and password', 'makaazi' ),
		'summary'  => __( 'Change your name, phone, email or password.', 'makaazi' ),
		'keywords' => 'profile password email phone name forgot',
		'body'     => __(
			'<ul>
<li>Update your details on <a href="{url:profile}">Profile</a>. Agents can also change the photo and bio on their public agent page.</li>
<li>To change your password, type a new one in <em>New password</em> on Profile and click <em>Save profile</em>.</li>
<li>Forgot your password? Use <em>Forgot your password?</em> on the sign-in form.</li>
</ul>',
			'makaazi'
		),
	),
	array(
		'id'       => 'acc-faq',
		'group'    => __( 'Enquiries & account', 'makaazi' ),
		'title'    => __( 'Questions', 'makaazi' ),
		'summary'  => __( 'Quick answers.', 'makaazi' ),
		'keywords' => 'faq questions cost how long',
		'body'     => __(
			'<p><strong>How long does review take?</strong> Usually within one working day.</p>
<p><strong>Why was my listing sent back?</strong> The reason is on the listing under <em>Needs changes</em> and in your email. Fix it and click <em>Submit for review</em> again.</p>
<p><strong>I cannot see “Add property”.</strong> Your account may still be waiting for approval, or listing may be paused on this site. Contact us.</p>
<p><strong>Can someone else manage my listing?</strong> Ask us to assign one of our agents.</p>',
			'makaazi'
		),
	),
);
