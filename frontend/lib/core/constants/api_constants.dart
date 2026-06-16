abstract class ApiConstants {
  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://localhost:8000/api/v1',
  );

  static const Duration connectTimeout = Duration(seconds: 30);
  static const Duration receiveTimeout = Duration(minutes: 2);
  static const Duration sendTimeout    = Duration(minutes: 2);

  // Auth
  static const String login    = '/auth/login';
  static const String register = '/auth/register';
  static const String refresh  = '/auth/refresh';
  static const String logout   = '/auth/logout';
  static const String me       = '/auth/me';

  // Organizations
  static const String organizations = '/organizations';
  static String organization(String id) => '/organizations/$id';
  static String orgMembers(String id) => '/organizations/$id/members';

  // Repositories
  static String repositories(String orgId) => '/organizations/$orgId/repositories';
  static String repository(String orgId, String repoId) =>
      '/organizations/$orgId/repositories/$repoId';

  // Projects
  static String projects(String orgId) => '/organizations/$orgId/projects';
  static String project(String orgId, String projectId) =>
      '/organizations/$orgId/projects/$projectId';

  // Analyses
  static String analyses(String orgId) => '/organizations/$orgId/analyses';
  static String analysis(String orgId, String analysisId) =>
      '/organizations/$orgId/analyses/$analysisId';
  static String analysisIssues(String orgId, String analysisId) =>
      '/organizations/$orgId/analyses/$analysisId/issues';
  static String analysisTests(String orgId, String analysisId) =>
      '/organizations/$orgId/analyses/$analysisId/tests';
  static String analysisReport(String orgId, String analysisId) =>
      '/organizations/$orgId/analyses/$analysisId/report';
}

abstract class StorageKeys {
  static const String accessToken       = 'access_token';
  static const String refreshToken      = 'refresh_token';
  static const String currentOrgId      = 'current_org_id';
  static const String onboardingComplete = 'onboarding_complete';
}
