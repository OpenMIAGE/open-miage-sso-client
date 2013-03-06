OpenM_SSO / client / example
=====================

Description
=====================
This package is provided by www.open-miage.org

Prerequis
=====================
 * PHP 5.2.x min
 * CURL extension.
 * GMP extension (or Bcmath, but GMP is mutch faster)

Example
=====================
This package used *.example.open-miage.fr services (all is already setup).

    To use ./example :
     * copy ./example and ./src under the same directory on a http server
     * launch ./example/ http url on server that host your example

Remarque
=====================
This package provide two examples:
 * an example without api (./example/withoutAPI/) using : for site that whant to use OpenM_ID connection for using their web site
 * an example with api (./example/withAPI/) using : for API client interface that will be implemented for next (ex.: OpenM_Book, etc.)

Attention
=====================
NB: to use example with api, you need to launch ./example/withAPI/installer.php and after ./example/withAPI/installer2.php
that will install your client under api called in example and add client rights to call all required method on api.
/!\attention/!\ you need to connect under OpenM-ID example with admin account (login: admin@openm.id / password: admin)

This package provide a generic source code for developpers:
 * under (./example/gen_code_for_my_client/) you have an installer (install.php and install2.php) to open access to an api, and the source code to access on it.