import 'package:flutter/material.dart';

class RepositoryListScreen extends StatelessWidget {
  const RepositoryListScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Repositories')),
      body: const Center(child: Text('Repository management coming soon')),
    );
  }
}
