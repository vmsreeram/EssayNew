The essay annotate question type is an extension to [essay question type](https://docs.moodle.org/404/en/Essay_question_type) that supports annotation of the uploaded files. PDF and file types with mime as image and text are supported.

# Installation steps
- Installing the plugin
  <details>
  <summary>Using GUI</summary>

  - Download the repository as a ZIP file by navigating to `Code` > `Local` > `Download ZIP` in the homepage of the repository, or using this [link](https://github.com/vmsreeram/essayannotate/archive/refs/heads/main.zip).
  - Login to Moodle as Admin
  - Navigate to `Site administration` > `Plugins` > `Install plugins`
  - Upload the ZIP file to `ZIP package` section.
  - Expand `Show more...`
  - Choose `Plugin type` as `Question type (qtype)`.
  - Rename the root directory to `essayannotate`.
  - Click on `Install plugin from ZIP file` and follow the prompted steps.
  </details>
  
  <details>
  <summary>Manually</summary>

  - Get the contents of the repo by either
    - Download the repository as a ZIP file by navigating to `Code` > `Local` > `Download ZIP` in the homepage of the repository, or using this [link](https://github.com/vmsreeram/essayannotate/archive/refs/heads/main.zip). Then unzip the repo, and rename the folder name to `essayannotate`.
    - Cloning this repo. Then rename the folder name to `essayannotate`.
  - Move this folder into the  `question/type` folder on your Moodle site, to create a new folder at `question/type/essayannotate`.
  - Login to Moodle as Admin 
  - Follow the prompted steps to install the plugin. If install/upgrade does not begin automatically, you can initiate it manually by navigating to the following link: `Administration` > `Site administration` > `Notifications`
  </details>

- Once installation is successful, you will be prompted to enter the paths to `convert` and `ghostscript`. You can install convert from [here](https://imagemagick.org/script/download.php) and ghostscript from [here](https://docs.bitnami.com/google/apps/resourcespace/configuration/install-ghostscript/). You can get the paths to `convert` and `gs` by using [`which`](https://en.wikipedia.org/wiki/Which_(command)).
- Update `rights="none"` to `rights="read|write"` in `/etc/ImageMagick-6/policy.xml` for `pattern="PDF"`.

  ```xml
  <!-- disable ghostscript format types -->
  <policy domain="coder" rights="none" pattern="PS" />
  <policy domain="coder" rights="none" pattern="EPS" />
  <policy domain="coder" rights="none" pattern="PDF" />  <!------- Here!! -->
  <policy domain="coder" rights="none" pattern="XPS" />
  ```
</details>

# The Team
| Project Mentor     |  Email ID              |
|--------------------|------------------------|
| [Dr. Jasine Babu](https://www.iitpkd.ac.in/people/jasine)    | jasine@iitpkd.ac.in    |

<table>
  <tr>
    <th>Developers</th>
    <th>Email ID</th>
    <th>Year</th>
    <th>Repo</th>
  </tr>
  <tr>
    <td><a href="https://www.linkedin.com/in/nideesh-n-22497a128/">Nideesh N</a></td>
    <td>nideeshnagarajan@gmail.com</td>
    <td rowspan=2>2023-24</td>
    <td rowspan=2><a href="https://github.com/vmsreeram/essayannotate">github.com/vmsreeram/essayannotate</a></td>
  </tr>
  <tr>
    <td><a href="https://www.linkedin.com/in/vm-sreeram/">VM Sreeram</a></td>
    <td>vm.sreeram02@gmail.com</td>
  </tr>
  <tr>
    <td><a href="https://www.linkedin.com/in/asha-jose-5b8031218/">Asha Jose</a></td>
    <td>ashaj0308@gmail.com</td>
    <td rowspan=2>2022-23</td>
    <td rowspan=2><a href="https://github.com/Parvathy-S-Kumar/Moodle_Quiz_PDF_Annotator">github.com/Parvathy-S-Kumar/Moodle_Quiz_PDF_Annotator</a></td>
  </tr>
  <tr>
    <td><a href="https://www.linkedin.com/in/parvathy-s-kumar-288aa01b2/">Parvathy S Kumar</a></td>
    <td>parvathyskumar187@gmail.com</td>
  </tr>
  <tr>
    <td><a href="https://www.linkedin.com/in/vishal-rao-910809190/">Vishal Rao</a></td>
    <td>raovishal487@gmail.com</td>
    <td rowspan=2>2021-22</td>
    <td rowspan=2><a href="https://github.com/TausifIqbal/moodle_quiz_annotator">github.com/TausifIqbal/moodle_quiz_annotator</a></td>
  </tr>
  <tr>
    <td><a href="https://www.linkedin.com/in/tausif-iqbal-ti/">Tausif Iqbal</a></td>
    <td>tausifiqbalti21@gmail.com</td>
  </tr>
</table>

Special thanks to Mr. Sumesh K S (Technical Supdt.) & Mr. Neeraj Patil for helping us in testing.

