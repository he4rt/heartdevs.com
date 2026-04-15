# He4rt Developers Platform

<p align="center">
  <a href="https://discord.gg/he4rt">
    <img src="./.github/logo.png" height="220" alt="He4rt Developers Logo">
  </a>
</p>

<h1 align="center">
He4rt Developers Platform
</h1>

<p align="center">
  <a href="https://discord.gg/he4rt"><img src="https://img.shields.io/github/repo-size/he4rt/heartdevs.com?style=for-the-badge"></a>
  <a href="https://discord.gg/he4rt"><img src="https://img.shields.io/github/languages/count/he4rt/heartdevs.com?style=for-the-badge"></a>
  <a href="https://discord.gg/he4rt"><img src="https://img.shields.io/github/forks/he4rt/heartdevs.com?style=for-the-badge"></a>
    <a href="https://discord.gg/he4rt"><img src="https://img.shields.io/github/stars/he4rt/heartdevs.com?style=for-the-badge&link=https%3A%2F%2Fgithub.com%2Fhe4rt%2Fheartdevs.com%2Fstargazers
    "></a>
  <a href="https://discord.gg/he4rt"><img src="https://img.shields.io/github/issues/he4rt/heartdevs.com?style=for-the-badge"></a>
  <a href="https://discord.gg/he4rt"><img src="https://img.shields.io/github/issues-pr/he4rt/heartdevs.com?style=for-the-badge"></a>
  <a href="https://discord.gg/he4rt"><img src="https://img.shields.io/github/license/he4rt/heartdevs.com?color=6342ED&style=for-the-badge"></a>
</p>

> This is the central monorepo application and Discord Bot for the **He4rt Developers** community, built on top of a modular Laravel architecture.

## 🧩 Modules Overview

This project is divided into several interconnected modules:
- **Activity**: Tracks user actions and engagement.
- **Bot Discord**: The core Discord bot functionality.
- **Community**: Forum, discussions, and community features.
- **Economy**: Virtual economy system for members.
- **Events**: Management of community events.
- **Gamification**: Badges, levels, and achievements.
- **Identity**: Centralized authentication and user profiles.
- **Panel Admin**: A Filament-powered administrative dashboard.
- **Portal**: The main web interface for users.
- **Integrations**: Integrations with external platforms like DevTo, Discord, and Twitch.

## 💻 Requirements

Before starting, ensure you have the following installed:
- PHP 8.3 or higher
- Composer
- Node.js & NPM
- Docker (for PostgreSQL & Redis)

## 🚀 Getting Started

1. **Start Infrastructure**:
   Spin up the required database and cache containers.
   ```bash
   make env-up
   ```

2. **Project Setup**:
   Install PHP and Node dependencies, generate keys, and link storage.
   ```bash
   make setup
   ```

3. **Run the Development Server**:
   Start the Laravel server, Vite, and required queues.
   ```bash
   make dev
   ```

4. **Run the Discord Bot**:
   Boot up the Discord bot locally.
   ```bash
   make bot
   ```

## 🧪 Code Quality & Testing

We use multiple tools to ensure code quality:

- **Tests**: Run the Pest test suite.
  ```bash
  make test
  ```
- **Code Styler**: Format code using Laravel Pint.
  ```bash
  make pint
  ```
- **Static Analysis**: Run PHPStan.
  ```bash
  make phpstan
  ```

## 📫 Contributing to He4rt Developers Platform

To contribute to this project, follow these steps:

1. Fork this repository.
2. Create a branch: `git checkout -b <branch_name>`.
3. Make your changes and commit them: `git commit -m '<commit_message>'`.
4. Push your branch: `git push -u origin <branch_name>`.
5. Create the pull request.

Alternatively, see the GitHub documentation on [how to create a pull request](https://help.github.com/en/github/collaborating-with-issues-and-pull-requests/creating-a-pull-request).

## 🤝 Contributors

Thanks go to these wonderful people:

<table>
  <tr>
    <td align="center">
      <a href="https://github.com/danielhe4rt" title="danielhe4rt">
        <img src="https://github.com/danielhe4rt.png" width="100px;" alt="danielhe4rt"/><br>
        <sub>
          <b>danielhe4rt</b>
        </sub>
      </a>
      <br />
      <a href="https://github.com/he4rt/heartdevs.com/commits?author=danielhe4rt" title="Code">💻</a>
    </td>
    <td align="center">
      <a href="https://github.com/PedroPMS" title="PedroPMS">
        <img src="https://github.com/PedroPMS.png" width="100px;" alt="PedroPMS"/><br>
        <sub>
          <b>PedroPMS</b>
        </sub>
      </a>
      <br />
      <a href="https://github.com/he4rt/heartdevs.com/commits?author=PedroPMS" title="Code">💻</a>
    </td>
    <td align="center">
      <a href="https://github.com/Clintonrocha98" title="Clintonrocha98">
        <img src="https://github.com/Clintonrocha98.png" width="100px;" alt="Clintonrocha98"/><br>
        <sub>
          <b>Clintonrocha98</b>
        </sub>
      </a>
      <br />
      <a href="https://github.com/he4rt/heartdevs.com/commits?author=Clintonrocha98" title="Code">💻</a>
    </td>
    <td align="center">
      <a href="https://github.com/gvieira18" title="gvieira18">
        <img src="https://github.com/gvieira18.png" width="100px;" alt="gvieira18"/><br>
        <sub>
          <b>gvieira18</b>
        </sub>
      </a>
      <br />
      <a href="https://github.com/he4rt/heartdevs.com/commits?author=gvieira18" title="Code">💻</a>
    </td>
    <td align="center">
      <a href="https://github.com/hehehenri" title="hehehenri">
        <img src="https://github.com/hehehenri.png" width="100px;" alt="hehehenri"/><br>
        <sub>
          <b>hehehenri</b>
        </sub>
      </a>
      <br />
      <a href="https://github.com/he4rt/heartdevs.com/commits?author=hehehenri" title="Code">💻</a>
    </td>
  </tr>
  <tr>
    <td align="center">
      <a href="https://github.com/rafael-neris" title="rafael-neris">
        <img src="https://github.com/rafael-neris.png" width="100px;" alt="rafael-neris"/><br>
        <sub>
          <b>rafael-neris</b>
        </sub>
      </a>
      <br />
      <a href="https://github.com/he4rt/heartdevs.com/commits?author=rafael-neris" title="Code">💻</a>
    </td>
    <td align="center">
      <a href="https://github.com/Canhassi12" title="Canhassi12">
        <img src="https://github.com/Canhassi12.png" width="100px;" alt="Canhassi12"/><br>
        <sub>
          <b>Canhassi12</b>
        </sub>
      </a>
      <br />
      <a href="https://github.com/he4rt/heartdevs.com/commits?author=Canhassi12" title="Code">💻</a>
    </td>
    <td align="center">
      <a href="https://github.com/RichardGL11" title="RichardGL11">
        <img src="https://github.com/RichardGL11.png" width="100px;" alt="RichardGL11"/><br>
        <sub>
          <b>RichardGL11</b>
        </sub>
      </a>
      <br />
      <a href="https://github.com/he4rt/heartdevs.com/commits?author=RichardGL11" title="Code">💻</a>
    </td>
    <td align="center">
      <a href="https://github.com/DiogoKaster" title="DiogoKaster">
        <img src="https://github.com/DiogoKaster.png" width="100px;" alt="DiogoKaster"/><br>
        <sub>
          <b>DiogoKaster</b>
        </sub>
      </a>
      <br />
      <a href="https://github.com/he4rt/heartdevs.com/commits?author=DiogoKaster" title="Code">💻</a>
    </td>
    <td align="center">
      <a href="https://github.com/PilsAraujo" title="PilsAraujo">
        <img src="https://github.com/PilsAraujo.png" width="100px;" alt="PilsAraujo"/><br>
        <sub>
          <b>PilsAraujo</b>
        </sub>
      </a>
      <br />
      <a href="https://github.com/he4rt/heartdevs.com/commits?author=PilsAraujo" title="Code">💻</a>
    </td>
  </tr>
  <tr>
    <td align="center">
      <a href="https://github.com/Novout" title="Novout">
        <img src="https://github.com/Novout.png" width="100px;" alt="Novout"/><br>
        <sub>
          <b>Novout</b>
        </sub>
      </a>
      <br />
      <a href="https://github.com/he4rt/heartdevs.com/commits?author=Novout" title="Code">💻</a>
    </td>
    <td align="center">
      <a href="https://github.com/matheusdsilva01" title="matheusdsilva01">
        <img src="https://github.com/matheusdsilva01.png" width="100px;" alt="matheusdsilva01"/><br>
        <sub>
          <b>matheusdsilva01</b>
        </sub>
      </a>
      <br />
      <a href="https://github.com/he4rt/heartdevs.com/commits?author=matheusdsilva01" title="Code">💻</a>
    </td>
    <td align="center">
      <a href="https://github.com/1pride" title="1pride">
        <img src="https://github.com/1pride.png" width="100px;" alt="1pride"/><br>
        <sub>
          <b>1pride</b>
        </sub>
      </a>
      <br />
      <a href="https://github.com/he4rt/heartdevs.com/commits?author=1pride" title="Code">💻</a>
    </td>
  </tr>
</table>

Contributions of any kind welcome!

## 🌐 Our Ecosystem & Socials

**Projects & Initiatives:**
- 🌐 [Website (heartdevs.com)](https://heartdevs.com/)
- 💻 [He4rt Developers Platform (This Repo)](https://github.com/he4rt/heartdevs.com)
- 🎓 [4noobs](https://github.com/he4rt/4noobs)
- 🧪 [He4rtLabs Challenges](https://github.com/he4rt/heartlabs-challenges)
- 💯 [100DiasDeCodigo](https://github.com/he4rt/100diasdecodigo)

**Social Media:**
- 💼 [LinkedIn](https://www.linkedin.com/company/he4rt/)
- 🐦 [X / Twitter](https://x.com/He4rtDevs)
- 📸 [Instagram](https://instagram.com/heartdevs)
- 👩‍💻 [Dev.to](https://dev.to/he4rt)

## 📝 License

This project is under the MIT license. See the [LICENSE](LICENSE) file for more details.

---
*Maintained by the [He4rt Developers](https://discord.gg/he4rt) team.*