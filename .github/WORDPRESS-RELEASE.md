# WordPress.org へのリリース手順

公開するバージョンの GitHub Release が完成してから、GitHub Actions の **Deploy to WordPress.org** を手動で起動します。この手順の対象は今後のリリースです。

## 初回設定

1. WordPress.org のプラグイン `omoikane-simple-sales-reports` に SVN コミット権限があるアカウントで、アカウント設定から SVN 専用パスワードを発行します。 [WordPress.org の SVN アカウント設定](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/#your-account)
2. GitHub リポジトリの **Settings → Environments** で `wordpress-org` を作成し、**Environment secrets** に次の 2 件を登録します。

   | 名前 | 値 |
   | --- | --- |
   | `SVN_USERNAME` | SVN コミット権限を持つ WordPress.org のユーザー名 |
   | `SVN_PASSWORD` | 発行した SVN 専用パスワード |

3. 公開承認を使う場合は `wordpress-org` の **Required reviewers** を設定します。デプロイの起動元を絞る場合は、同じ Environment の **Deployment branches and tags** で `main` ブランチを許可します。dry-run 用の `wordpress-org-dry-run` は Secrets と承認設定がなくても使えます。 [GitHub の Environment 設定](https://docs.github.com/en/actions/how-tos/deploy/configure-and-manage-deployments/manage-environments)

## dry-run と公開

1. Git Flow のリリースを `main` に反映し、公開予定の `v<version>` タグから既存の **Build Release Zip** が完了したことを確認します。公開済み GitHub Release に `omoikane-simple-sales-reports-<version>.zip` が必要です。
2. **Actions → Deploy to WordPress.org → Run workflow** を開きます。ワークフローファイルが既定ブランチの `main` に入ると起動できます。実行ブランチは通常 `main` を選びます。同じワークフローが `develop` にあれば、そこからも dry-run できます。取得する ZIP は入力バージョンのタグに対応する Release で決まります。 [GitHub の手動起動](https://docs.github.com/en/actions/how-tos/manage-workflow-runs/manually-run-a-workflow)
3. `version` に `1.2.0` のように先頭の `v` を付けずに入力します。`dry_run` をオンのまま実行し、Actions のログで ZIP の SHA-256、バージョン検証、SVN の変更予定を確認します。dry-run は SVN の作業コピーまで作り、コミットをスキップします。
4. 公開するときは同じバージョンで改めて起動し、`dry_run` をオフにします。承認を設定している場合は **Review deployments** から承認します。SVN に同じバージョンのタグがあればエラーで停止します。公開後、WordPress.org 側で Release Confirmation を有効にしている場合は、同サービスのダッシュボードでもリリースを承認します。 [WordPress.org の Release Confirmation](https://developer.wordpress.org/plugins/wordpress-org/release-confirmation-emails/)

## ZIP と SVN の対応

ZIP 内の `omoikane-simple-sales-reports/` を展開し、メイン PHP と `readme.txt` が直接入っているディレクトリを `BUILD_DIR` に渡します。Action がその中身を SVN の `trunk/` に同期し、`tags/<version>/` を作ります。ZIP 本体と最上位のプラグインフォルダーは同期対象外です。

WordPress.org のスクリーンショットなどを置く SVN 直下の `assets/` はコミット対象外です。10up Action は MIME 属性の変更をローカルに表示することがあるため、ワークフローでコミット対象を `trunk/` と指定バージョンのタグに限定しています。ZIP 内の実行時アセットはプラグインと一緒に同期されます。 [10up Action 2.3.0 の実装](https://github.com/10up/action-wordpress-plugin-deploy/blob/54bd289b8525fd23a5c365ec369185f2966529c2/deploy.sh)
