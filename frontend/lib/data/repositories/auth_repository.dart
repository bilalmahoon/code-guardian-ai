import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../models/user_model.dart';
import '../../core/constants/api_constants.dart';
import '../../core/network/dio_client.dart';

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepository(ref.watch(dioClientProvider));
});

final authStateProvider = FutureProvider<UserModel?>((ref) async {
  return ref.watch(authRepositoryProvider).getCurrentUser();
});

class AuthRepository {
  final DioClient _client;
  final _storage = const FlutterSecureStorage();

  AuthRepository(this._client);

  Future<({UserModel user, String accessToken, String refreshToken})> login({
    required String email,
    required String password,
  }) async {
    final response = await _client.dio.post(
      ApiConstants.login,
      data: {'email': email, 'password': password},
    );

    final data = response.data['data'] as Map<String, dynamic>;

    await _storage.write(
      key: StorageKeys.accessToken,
      value: data['access_token'] as String,
    );
    await _storage.write(
      key: StorageKeys.refreshToken,
      value: data['refresh_token'] as String,
    );

    return (
      user: UserModel.fromJson(data['user'] as Map<String, dynamic>),
      accessToken: data['access_token'] as String,
      refreshToken: data['refresh_token'] as String,
    );
  }

  Future<({UserModel user, String accessToken, String refreshToken})> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    final response = await _client.dio.post(
      ApiConstants.register,
      data: {
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
      },
    );

    final data = response.data['data'] as Map<String, dynamic>;

    await _storage.write(
      key: StorageKeys.accessToken,
      value: data['access_token'] as String,
    );

    return (
      user: UserModel.fromJson(data['user'] as Map<String, dynamic>),
      accessToken: data['access_token'] as String,
      refreshToken: '',
    );
  }

  Future<UserModel?> getCurrentUser() async {
    final token = await _storage.read(key: StorageKeys.accessToken);

    if (token == null) return null;

    try {
      final response = await _client.dio.get(ApiConstants.me);
      return UserModel.fromJson(
        response.data['data']['user'] as Map<String, dynamic>,
      );
    } catch (_) {
      return null;
    }
  }

  Future<void> logout() async {
    try {
      await _client.dio.post(ApiConstants.logout);
    } finally {
      await _storage.delete(key: StorageKeys.accessToken);
      await _storage.delete(key: StorageKeys.refreshToken);
      await _storage.delete(key: StorageKeys.currentOrgId);
    }
  }

  Future<bool> isLoggedIn() async {
    final token = await _storage.read(key: StorageKeys.accessToken);
    return token != null;
  }
}
