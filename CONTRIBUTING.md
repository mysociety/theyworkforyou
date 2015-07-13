Thanks for contributing to TheyWorkForYou!

* [Coding standards](https://mysociety.github.io/coding-standards.html)
* [Ticket management](https://waffle.io/mysociety/theyworkforyou)

Workflow
--------

* Icebox – Untriaged tickets, or those that are on the back burner;
* Backlog – Tickets we would like to do at some point;
* Current – Scheduled to be done in the current sprint;
* In progress – Currently being worked on;
* Reviewing – Work has been done and is awaiting review or being reviewed;
* Reviewed – The PR has been reviewed, and so needs either more work (and then
  put back in Reviewing for further review), or to be merged and deployed;
* Done – Tickets placed here will be automatically closed.

If you are working on a ticket or reviewing a pull request, assign it to yourself so others know it is taken.

Tips
----

If you create a new branch that starts with “NNN-” or “NNN_” or has “#NNN”
anywhere in it, then pushing that branch will automatically move an issue to In
progress.

When you create a pull request, if it fixes a current issue put
“Fixes/closes/resolves #NNN” in the PR title/description, and then the PR will
be linked with the issue. If it is only linked to an issue, but
doesn’t fix it, put “connect/connects/connected to #NNN” in the details.

New PRs from collaborators will appear in Reviewing, and new PRs from
non-collaborators will appear in Current (so that they are hopefully seen and
triaged quickly).
