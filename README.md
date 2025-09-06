# onlinejudge
要件（Requirements）
Linux（例: Ubuntu 22.04 / RockyLinux 9）
PHP 8.2+ / Composer
Node.js 18+ / npm
PostgreSQL 15+（MySQLでも可・本READMEはPostgreSQL想定）
Redis 6+（キュー用）
isolate（サンドボックス）
Ubuntu: sudo apt-get install isolate
Rocky: EPEL もしくはソースからインストール

実行系（ホストにインストール）
g++（C++17）
python3
javac(Java)

主要ファイルの説明
Path	役割
app/Http/Controllers/ProblemController.php	問題一覧/詳細の表示（Inertia で React ページへ）
app/Http/Controllers/SubmissionController.php	提出の受け付け・ステータス取得（JSON）
app/Jobs/Judge/JudgeSubmissionJob.php	提出をキューで処理し IsolateJudge を呼び出す
app/Services/Judge/IsolateJudge.php	isolate でコンパイル/実行/比較、サブタスク集計
app/Models/Problem.php	問題メタ情報（制限・配点・対応言語）
app/Models/Submission.php	提出（言語/コード/判定/得点/詳細）
config/judge.php	言語定義（src/compile/run）と既定の制限値
resources/js/Pages/Problems/Index.jsx	問題一覧 UI（Tailwind）
resources/js/Pages/Problems/Show.jsx	制約表示・言語選択・提出・結果表示
resources/js/Components/Badge.jsx	判定（AC/WA/TLE/RE/CE/PARTIAL…）の色分け表示
routes/web.php	/problems, /problems/{slug} などのページルート
routes/api.php	/api/submissions/{id} などの API ルート
storage/app/problems/**	問題パッケージ（problem.yml と tests/*.in/out）
storage/app/runs/**	実行時の一時ディレクトリ（自動削除）

project-root/
├─ app/
│  ├─ Http/
│  │  └─ Controllers/
│  │     ├─ ProblemController.php         # 問題一覧/詳細（Inertiaページ）
│  │     └─ SubmissionController.php      # 提出API（作成/参照）
│  ├─ Jobs/
│  │  └─ Judge/
│  │     └─ JudgeSubmissionJob.php        # キュー上の採点ジョブ
│  ├─ Models/
│  │  ├─ Problem.php                      # 問題モデル
│  │  └─ Submission.php                   # 提出モデル
│  └─ Services/
│     └─ Judge/
│        └─ IsolateJudge.php              # サンドボックス実行（isolate）
│
├─ config/
│  └─ judge.php                           # 言語定義・デフォルト制限
│
├─ database/
│  └─ migrations/
│     ├─ 2025_09_06_000001_create_problems_table.php
│     └─ 2025_09_06_000002_create_submissions_table.php
│
├─ resources/
│  └─ js/
│     ├─ Components/
│     │  └─ Badge.jsx                     # Verdictバッジ（Tailwind）
│     └─ Pages/
│        └─ Problems/
│           ├─ Index.jsx                  # 問題一覧（カードUI）
│           └─ Show.jsx                   # 詳細・提出フォーム・結果表示
│
├─ routes/
│  ├─ web.php                             # Inertiaページ用ルート
│  └─ api.php                             # 提出ステータス取得API
│
├─ storage/
│  └─ app/
│     ├─ problems/
│     │  └─ abc001_a/                     # サンプル問題（A+B）
│     │     ├─ problem.yml
│     │     └─ tests/
│     │        ├─ sample1.{in,out}
│     │        ├─ 01.{in,out}
│     │        ├─ 02.{in,out}
│     │        └─ 03.{in,out}
│     └─ runs/                            # 一時作業領域（自動生成・自動削除）
│
├─ .env                                   # 環境設定
└─ （標準のLaravel/フロント構成）

クイックスタート

# 依存をインストール
composer install
npm install

# .env 設定（DB/Redis等）
cp .env.example .env
# APP_KEY生成
php artisan key:generate

# マイグレーション
php artisan migrate

# フロントエンド（Vite）
npm run dev

# キューワーカー（別ターミナル）
php artisan queue:work

# Web 起動
php artisan serve  # http://127.0.0.1:8000



主要ファイルの役割
バックエンド

app/Models/Problem.php
問題のメタ情報（制限・配点・対応言語など）を保持します。

allowed_langs（array）: 利用可能言語（例: ["cpp","python"]）

scoring（array）: サブタスク配点など

app/Models/Submission.php
提出の状態・得点・実行時間・詳細（ケースごとの結果）を保持します。

app/Http/Controllers/ProblemController.php

index()：問題一覧ページ（Inertia/React）

show(slug)：問題詳細ページ

app/Http/Controllers/SubmissionController.php

store()：提出受付（lang, code）。DB保存 → 採点ジョブをキュー投入

show(id)：提出結果をJSONで返却

app/Jobs/Judge/JudgeSubmissionJob.php
提出IDを受け取り、IsolateJudge を呼び出して採点。
結果（AC/WA/TLE/RE/CE/PARTIAL、得点、ケース結果）を submissions.detail に保存。

app/Services/Judge/IsolateJudge.php
isolate を呼び出して安全にコンパイル/実行/比較を行います。

設定は config/judge.php の languages を参照

各テストケースごとに入出力ファイルを用意し、標準入出力を束ねて実行

サブタスク配点（sum_subtasks）または全完クリア（all_or_nothing）に対応

config/judge.php

time_limit_ms_default, memory_limit_mb_default

languages：言語ごとの src（ソース名）, compile コマンド, run コマンド

```
'languages' => [
  'cpp' => [
    'src'     => 'main.cpp',
    'compile' => ['/usr/bin/g++','-O2','-std=gnu++17','/work/main.cpp','-o','/work/Main'],
    'run'     => ['/work/Main'],
  ],
  'python' => [
    'src'     => 'main.py',
    'compile' => null,
    'run'     => ['/usr/bin/python3','/work/main.py'],
  ],
],
```

ルーティング

routes/web.php

/problems（一覧ページ）

/problems/{slug}（詳細・提出ページ）

/problems/{slug}/submit（提出POST）

routes/api.php

/api/submissions/{id}（提出の最新状態を取得）

フロントエンド（React + Tailwind）

resources/js/Pages/Problems/Index.jsx
問題一覧をカードで表示。制限（時間/メモリ）・対応言語のバッジ表示。

resources/js/Pages/Problems/Show.jsx
制限/サブタスク表示、言語選択、コードエディタ（textarea）、提出ボタン、
提出ステータスのポーリング（JSON）と Badge 表示。

resources/js/Components/Badge.jsx
AC/WA/TLE/RE/CE/PARTIAL/QUEUED/RUNNING を色分け表示（Tailwind）。


問題パッケージ仕様（storage/app/problems/**/problem.yml）

```
slug: abc001_a
title: Addition
time_limit_ms: 2000
memory_limit_mb: 256
allowed_langs:
  - cpp
  - python
tests:
  - { in: "tests/sample1.in", out: "tests/sample1.out", group: "sample" }
  - { in: "tests/01.in",      out: "tests/01.out",      group: "small"  }
  - { in: "tests/02.in",      out: "tests/02.out",      group: "small"  }
  - { in: "tests/03.in",      out: "tests/03.out",      group: "large"  }
scoring:
  type: sum_subtasks    # or all_or_nothing
  groups:
    sample: 0           # サンプルは得点0
    small: 200
    large: 300
```

tests/*.in と tests/*.out は 厳密一致（改行・空白含む）。

サブタスクは group 単位で採点。グループ内のケースは1つでも落ちると0点。

all_or_nothing の場合は全ケースACで満点、そうでなければ0。

ジャッジの流れ（内部）

フロントから POST /problems/{slug}/submit（lang, code）

submissions に保存 → JudgeSubmissionJob を Redis キューに投入

ジョブ実行：

config/judge.php から言語設定を取得

一時作業ディレクトリ（storage/app/runs/<rand>）を作成

必要ならコンパイル → 各テストを isolate で実行（ネットワーク遮断 / 時間・メモリ・プロセス数 制限）

出力を期待解と比較 → ケース結果とサブタスク集計

submissions.detail に JSON で格納、status/points/time_ms を更新

一時ディレクトリを削除

セキュリティとリソース制限（isolate）

ネットワーク：デフォルトで無効

CPU時間/メモリ/プロセス数：実行ごとに --time / --mem / --processes で制限

ファイルアクセス：/work のみ書込、標準ディレクトリは :ro（読み取り専用）でマウント

一時ファイル：storage/app/runs/ 配下に作成→毎回削除

※ 追加で seccomp/rlimit 等の強化や専用ユーザー（例：ojrun）での実行を推奨。

.env
```
APP_NAME="OJ"
APP_ENV=local
APP_KEY=base64:（php artisan key:generateで生成）
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

# DB（PostgreSQLの例）
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=oj
DB_USERNAME=oj
DB_PASSWORD=ojpass

# Queue（Redis）
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```


API

```
curl -X POST http://127.0.0.1:8000/problems/abc001_a/submit \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -H "X-CSRF-TOKEN: $(php artisan csrf:token)" \
  -d '{"lang":"cpp","code":"#include <bits/stdc++.h>\nusing namespace std;int main(){ long long a,b; if(!(cin>>a>>b)) return 0; cout<<a+b<<\"\\n\";}"}'
# => {"submission_id": 1}
```
ステータス取得
```
curl http://127.0.0.1:8000/api/submissions/1
```
言語追加

config/judge.php の languages に定義を1つ追加するだけです。
例（Java）:
```
'java' => [
  'src'     => 'Main.java',
  'compile' => ['/usr/bin/javac','/work/Main.java'],
  'run'     => ['/usr/bin/java','-Xss256m','-Xmx256m','-Dfile.encoding=UTF-8','-classpath','/work','Main'],
],
```

トラブルシューティング

isolate が見つからない：which isolate でパス確認。インストール・権限を見直す

TLE/REばかり：config/judge.php の制限を緩める（時間/メモリ）。/var/local/lib/isolate 権限や cgroups を確認

キューが動かない：php artisan queue:work のログ確認、Redis 接続確認

比較が合わない：改行・末尾スペースに注意。trim() で比較していますが、期待出力の末尾改行差で落ちやすい場合はSPJ導入を検討

ライセンス / 注意

この構成は学習用・MVP 用です。
本番運用では追加の監査ログ、Rate Limit、CSRF/認可の強化、監視（Prometheus/Grafana）、WAF などの整備を推奨します。